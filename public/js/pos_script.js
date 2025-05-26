document.addEventListener('DOMContentLoaded', function () {
    console.log('pos_script.js: DOMContentLoaded event fired.');

    // Get DOM elements
    const barcodeInput = document.getElementById('barcodeInput');
    const lookupProductBtn = document.getElementById('lookupProductBtn');
    const cartItemsTableBody = document.getElementById('cartItems');
    const cartTotalSpan = document.getElementById('cartTotal');
    const clearCartBtn = document.getElementById('clearCartBtn');
    const completeSaleBtn = document.getElementById('completeSaleBtn');
    const holdSaleBtn = document.getElementById('holdSaleBtn'); // Get the hold sale button

    // Payment details elements
    const discountInput = document.getElementById('discountInput');
    const taxRateInput = document.getElementById('taxRateInput'); // Corrected ID from 'taxInput'
    const paymentMethodSelect = document.getElementById('paymentMethod'); // Corrected ID from 'paymentMethodSelect'
    const cashReceivedInput = document.getElementById('cashReceived');
    const changeDueSpan = document.getElementById('changeDue'); // This span shows the calculated change on the POS screen

    // --- Receipt Template DOM Elements ---
    // These must match the IDs in your pos_system.php's #receipt-template
    const receiptTemplate = document.getElementById('receipt-template');
    const receiptDateSpan = document.getElementById('receipt-date');
    const receiptSaleIdSpan = document.getElementById('receipt-sale-id');
    const receiptCashierSpan = document.getElementById('receipt-cashier');
    const receiptItemsTableBody = document.getElementById('receipt-items'); // Note: This is different from cartItemsTableBody
    const receiptSubtotalSpan = document.getElementById('receipt-subtotal');
    const receiptDiscountPercentSpan = document.getElementById('receipt-discount-percent');
    const receiptDiscountAmountSpan = document.getElementById('receipt-discount-amount');
    const receiptTaxPercentSpan = document.getElementById('receipt-tax-percent');
    const receiptTaxAmountSpan = document.getElementById('receipt-tax-amount');
    const receiptGrandTotalSpan = document.getElementById('receipt-grand-total');
    const receiptPaymentMethodSpan = document.getElementById('receipt-payment-method');
    const receiptCashReceivedRow = document.getElementById('receipt-cash-received-row');
    const receiptCashReceivedSpan = document.getElementById('receipt-cash-received');
    const receiptChangeDueRow = document.getElementById('receipt-change-due-row');
    const receiptChangeDueSpan = document.getElementById('receipt-change-due');
    // --- End Receipt Template DOM Elements ---


    // Cart data structure
    let cart = []; // Array to hold cart items

    // --- Utility Functions ---

    // Function to format currency (e.g., adds "₱ " and fixes to 2 decimal places)
    function formatCurrency(amount) {
        return `₱ ${parseFloat(amount).toFixed(2)}`;
    }

    // Function to update the cart display in the HTML table
    function updateCartDisplay() {
        console.log('Updating cart display...');
        cartItemsTableBody.innerHTML = ''; // Clear existing items in the table

        if (cart.length === 0) {
            // Display 'No items' message if cart is empty
            cartItemsTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No items in cart yet. Scan a product!</td>
                </tr>
            `;
        } else {
            // Populate table with cart items
            cart.forEach(item => {
                const row = cartItemsTableBody.insertRow();
                row.dataset.productId = item.id; // Store product ID on the row for easy access

                // *** START OF QUANTITY INPUT HTML CHANGE ***
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>${formatCurrency(item.price)}</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary quantity-minus" type="button" data-product-id="${item.id}">-</button>
                            <input type="text" class="form-control text-center item-quantity" value="${item.quantity}" data-product-id="${item.id}" readonly>
                            <button class="btn btn-outline-secondary quantity-plus" type="button" data-product-id="${item.id}">+</button>
                        </div>
                    </td>
                    <td>${formatCurrency(item.price * item.quantity)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-item" data-product-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                // *** END OF QUANTITY INPUT HTML CHANGE ***
            });
        }
        calculateTotals(); // Always recalculate and update total after display changes
    }

    // Function to add a product to the cart or update its quantity if already present
    function addProductToCart(product) {
        const existingItemIndex = cart.findIndex(item => item.id === product.id);

        if (existingItemIndex > -1) {
            // Product already in cart, increment quantity
            if (cart[existingItemIndex].quantity < product.stock_quantity) {
                cart[existingItemIndex].quantity += 1;
                console.log(`Increased quantity for ${product.name}. New qty: ${cart[existingItemIndex].quantity}`);
            } else {
                alert(`Cannot add more than available stock (${product.stock_quantity}) for ${product.name}.`);
            }
        } else {
            // Add new product to cart
            if (product.stock_quantity > 0) {
                cart.push({
                    id: product.id,
                    name: product.name,
                    barcode: product.barcode,
                    price: parseFloat(product.price), // Ensure price is a number
                    quantity: 1,
                    stock_quantity: parseInt(product.stock_quantity) // Store stock quantity from the database
                });
                console.log(`Added new product to cart: ${product.name}`);
            } else {
                alert(`Product "${product.name}" is out of stock.`);
            }
        }
        updateCartDisplay(); // Update display and total
        barcodeInput.value = ''; // Clear the barcode input after adding
        barcodeInput.focus(); // Keep focus on the barcode input for next scan
    }

    // Function to handle quantity changes (increment/decrement) for a cart item
    function handleQuantityChange(productId, change) {
        const itemIndex = cart.findIndex(item => item.id == productId);
        if (itemIndex > -1) {
            const item = cart[itemIndex];
            let newQuantity = item.quantity + change;

            if (newQuantity < 1) {
                // If trying to go below 1, remove the item
                removeItemFromCart(productId);
                return; // Exit function after removal
            }

            // Check against available stock from the database
            if (newQuantity > item.stock_quantity) {
                alert(`Cannot add more than available stock (${item.stock_quantity}) for ${item.name}.`);
                newQuantity = item.stock_quantity; // Cap at available stock
            }

            item.quantity = newQuantity;
            updateCartDisplay(); // Re-render the cart table
        }
    }

    // Function to remove an item completely from the cart
    function removeItemFromCart(productId) {
        // Use a confirm dialog for removal
        if (confirm('Are you sure you want to remove this item from the cart?')) {
            cart = cart.filter(item => item.id != productId); // Create new array without the removed item
            console.log(`Removed item with ID: ${productId}`);
            updateCartDisplay(); // Re-render the cart table
        }
    }

    // Function to calculate and update all totals (subtotal, discount, tax, grand total)
    function calculateTotals() {
        let subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discountPercentage = parseFloat(discountInput.value) || 0;
        const taxRatePercentage = parseFloat(taxRateInput.value) || 0;

        let discountAmount = subtotal * (discountPercentage / 100);
        let totalAfterDiscount = subtotal - discountAmount;
        let taxAmount = totalAfterDiscount * (taxRatePercentage / 100);
        let grandTotal = totalAfterDiscount + taxAmount;

        cartTotalSpan.textContent = formatCurrency(grandTotal);

        // Update change due if payment method is cash
        updateChangeDue();
    }

    // Function to update change due
    function updateChangeDue() {
        const grandTotal = parseFloat(cartTotalSpan.textContent.replace('₱ ', ''));
        const cashReceived = parseFloat(cashReceivedInput.value) || 0;

        if (paymentMethodSelect.value === 'Cash') {
            const change = cashReceived - grandTotal;
            changeDueSpan.textContent = formatCurrency(change);
        } else {
            changeDueSpan.textContent = formatCurrency(0); // No change for non-cash payments
        }
    }

    // Function to clear the entire cart
    function clearCart() {
        if (confirm('Are you sure you want to clear the entire cart?')) {
            cart = []; // Reset cart array
            updateCartDisplay(); // Update display to show empty cart
            console.log('Cart cleared.');
            // Reset other payment inputs
            discountInput.value = '0';
            taxRateInput.value = '12';
            paymentMethodSelect.value = 'Cash';
            cashReceivedInput.value = '';
            updateChangeDue(); // Recalculate and display change
        }
    }

    // --- Core Lookup Function ---

    // Function to fetch product data from the API based on barcode
    async function lookupProduct() {
        console.log('lookupProduct function called.');
        const barcode = barcodeInput.value.trim();
        console.log('Barcode entered:', barcode);

        if (barcode === '') {
            alert('Please scan or type a barcode.');
            return;
        }

        try {
            console.log('Attempting to fetch from:', `../api/products.php?search=${barcode}`);
            const response = await fetch(`../api/products.php?search=${barcode}`);

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }

            const data = await response.json();
            console.log('Product data received:', data);

            if (data.success && data.products && data.products.length > 0) {
                let foundProduct = data.products.find(p => p.barcode === barcode);

                if (!foundProduct) {
                    foundProduct = data.products[0];
                }

                if (foundProduct) {
                    if (foundProduct.stock_quantity > 0) {
                        addProductToCart(foundProduct);
                    } else {
                        alert(`Product "${foundProduct.name}" is out of stock.`);
                        barcodeInput.value = '';
                        barcodeInput.focus();
                    }
                } else {
                    alert('Product not found for the given barcode.');
                    barcodeInput.value = '';
                    barcodeInput.focus();
                }
            } else {
                alert('Product not found.');
                barcodeInput.value = '';
                barcodeInput.focus();
            }
        } catch (error) {
            console.error('Error fetching product:', error);
            alert('Failed to lookup product. Check console for details (F12).');
        }
    }

    // --- Sale Management Functions ---

    // Placeholder for holdSale function (implement as needed)
    function holdSale() {
        alert('Hold Sale functionality is not yet implemented.');
        // Implement logic to save current cart to a 'held sales' list
        // and then clear the current cart.
    }

    async function completeSale() {
        console.log("completeSale: Function started.");
        if (cart.length === 0) {
            alert('Cart is empty. Please add items before completing a sale.');
            return;
        }

        const grandTotal = parseFloat(cartTotalSpan.textContent.replace('₱ ', ''));
        const discountPercentage = parseFloat(discountInput.value) || 0; // Renamed from discountAmount for clarity in percentage
        const taxRate = parseFloat(taxRateInput.value) || 0;
        const paymentMethod = paymentMethodSelect.value;
        const cashReceived = parseFloat(cashReceivedInput.value) || 0;

        // Calculate changeDue for sending to API and receipt
        const changeDue = cashReceived - grandTotal;

        if (paymentMethod === 'Cash' && cashReceived < grandTotal) {
            alert('Cash received is less than the total amount.');
            return;
        }

        let subtotalForTax = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        let calculatedDiscountValue = subtotalForTax * (discountPercentage / 100);
        let totalAfterDiscountForTax = subtotalForTax - calculatedDiscountValue;
        let calculatedTaxAmount = totalAfterDiscountForTax * (taxRate / 100);

        // Final confirmation prompt (showing calculated values)
        if (!confirm(`Confirm sale for total: ${formatCurrency(grandTotal)}?` +
            `\nDiscount Applied: ${discountPercentage}% (${formatCurrency(calculatedDiscountValue)})` +
            `\nTax Applied: ${taxRate}% (${formatCurrency(calculatedTaxAmount)})` +
            `\nPayment Method: ${paymentMethod}` +
            (paymentMethod === 'Cash' ? `\nCash Received: ${formatCurrency(cashReceived)}\nChange Due: ${formatCurrency(changeDue)}` : '') // Use calculated changeDue
        )) {
            return; // User cancelled
        }

        try {
            const response = await fetch('../api/complete_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart: cart,
                    total_amount: grandTotal,
                    discount_amount: calculatedDiscountValue, // Send actual amount, not percentage
                    tax_amount: calculatedTaxAmount,         // Send actual amount, not percentage
                    payment_method: paymentMethod,
                    cash_received: cashReceived,
                    change_due: changeDue // <<< CRITICAL FIX: SENDING change_due to API
                    // customer_id: customerId // Add this if you implement customer selection
                }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(`Server error: ${errorData.message || response.statusText}`);
            }

            const result = await response.json();

            if (result.success) {
                alert('Sale completed successfully! Sale ID: ' + result.sale_id);

                // --- Populate and Print Receipt ---
                console.log("completeSale: Calling populateReceipt...");
                // Pass all necessary calculated values to populateReceipt
                populateReceipt(
                    result.sale_id,
                    cart,
                    grandTotal,
                    subtotalForTax,
                    calculatedDiscountValue,
                    discountPercentage,
                    calculatedTaxAmount,
                    taxRate,
                    paymentMethod,
                    cashReceived,
                    changeDue // <<< CRITICAL FIX: PASSING changeDue to populateReceipt
                );
                console.log("completeSale: populateReceipt finished.");
                // --- End Print Receipt ---

                // Clear cart and reset inputs after successful sale and receipt printing
                cart = [];
                updateCartDisplay();
                discountInput.value = '0';
                taxRateInput.value = '12';
                paymentMethodSelect.value = 'Cash';
                cashReceivedInput.value = '';
                updateChangeDue();
            } else {
                alert('Failed to complete sale: ' + (result.message || 'Unknown error.'));
            }
        } catch (error) {
            console.error('Error completing sale:', error);
            alert('An error occurred while completing the sale. Check console for details: ' + error.message);
        }
    }


    // --- New Function to Populate and Print Receipt ---
    // Updated signature to accept all necessary values
    function populateReceipt(saleId, cartItems, grandTotal, subtotal, discountAmount, discountPercent, taxAmount, taxPercent, paymentMethod, cashReceived, changeDue) {
        console.log("populateReceipt: Function started with sale ID:", saleId);

        // Populate header details
        receiptDateSpan.textContent = new Date().toLocaleString();
        receiptSaleIdSpan.textContent = saleId;
        // receiptCashierSpan is already populated by PHP in pos_system.php, but if you want to set it here dynamically:
        // receiptCashierSpan.textContent = "Your Cashier Name"; // Or pass it as an argument

        receiptItemsTableBody.innerHTML = ''; // Clear previous items
        cartItems.forEach(item => {
            const row = receiptItemsTableBody.insertRow();
            // Using inline styles for basic receipt formatting
            row.innerHTML = `
                <td style="text-align: left;">${item.name}</td>
                <td style="text-align: center;">${item.quantity}</td>
                <td style="text-align: right;">${formatCurrency(item.price)}</td>
                <td style="text-align: right;">${formatCurrency(item.price * item.quantity)}</td>
            `;
        });

        // Populate summary details
        receiptSubtotalSpan.textContent = formatCurrency(subtotal);
        receiptDiscountPercentSpan.textContent = discountPercent.toFixed(2);
        receiptDiscountAmountSpan.textContent = formatCurrency(discountAmount);
        receiptTaxPercentSpan.textContent = taxPercent.toFixed(2);
        receiptTaxAmountSpan.textContent = formatCurrency(taxAmount);
        receiptGrandTotalSpan.textContent = formatCurrency(grandTotal);
        receiptPaymentMethodSpan.textContent = paymentMethod;

        // Show/hide cash received and change due rows based on payment method
        if (paymentMethod === 'Cash') {
            receiptCashReceivedRow.style.display = 'block';
            receiptChangeDueRow.style.display = 'block';
            receiptCashReceivedSpan.textContent = formatCurrency(cashReceived);
            receiptChangeDueSpan.textContent = formatCurrency(changeDue); // Display changeDue
        } else {
            receiptCashReceivedRow.style.display = 'none';
            receiptChangeDueRow.style.display = 'none';
        }

        console.log("populateReceipt: All elements populated. Calling window.print()...");
        // Trigger the print dialog
        window.print();
        console.log("populateReceipt: window.print() called.");
    }
    // --- End New Function to Populate and Print Receipt ---


    // --- Event Listeners ---

    // Event listener for Lookup button click
    if (lookupProductBtn) {
        lookupProductBtn.addEventListener('click', lookupProduct);
        console.log('Event listener attached to lookupProductBtn.');
    }

    // Event listener for barcode input (Enter key press)
    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                console.log('Enter key pressed in barcode input.');
                e.preventDefault(); // Prevent default form submission behavior (if input is part of a form)
                lookupProduct();
            }
        });
    }

    // Event listener for Clear Cart button
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', clearCart);
        console.log('Event listener attached to clearCartBtn.');
    }

    // Event listener for Complete Sale button
    if (completeSaleBtn) {
        completeSaleBtn.addEventListener('click', completeSale);
        console.log('Event listener attached to completeSaleBtn.');
    }

    // Event listener for Hold Sale button
    if (holdSaleBtn) {
        holdSaleBtn.addEventListener('click', holdSale);
        console.log('Event listener attached to holdSaleBtn.');
    }

    // Event listener for discount input changes
    if (discountInput) {
        discountInput.addEventListener('input', calculateTotals);
    }

    // Event listener for tax rate input changes
    if (taxRateInput) {
        taxRateInput.addEventListener('input', calculateTotals);
    }

    // Event listener for payment method changes (to show/hide cash section)
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function () {
            const cashSection = document.getElementById('cashPaymentSection');
            if (this.value === 'Cash') {
                cashSection.style.display = 'block';
                cashReceivedInput.focus(); // Focus on cash received when selected
            } else {
                cashSection.style.display = 'none';
            }
            updateChangeDue(); // Update change display based on payment method
        });
        // Initial state
        const cashSection = document.getElementById('cashPaymentSection');
        if (paymentMethodSelect.value !== 'Cash') {
            cashSection.style.display = 'none';
        }
    }

    // Event listener for cash received input changes
    if (cashReceivedInput) {
        cashReceivedInput.addEventListener('input', updateChangeDue);
    }

    // Event delegation for dynamically added cart item buttons (+, -, remove)
    cartItemsTableBody.addEventListener('click', function (event) {
        const target = event.target;
        const relevantButton = target.closest('.quantity-plus, .quantity-minus, .remove-item'); // Updated class names

        if (relevantButton) {
            const productId = relevantButton.dataset.productId;

            if (relevantButton.classList.contains('quantity-plus')) { // Updated class name
                handleQuantityChange(productId, 1); // Increment quantity
            } else if (relevantButton.classList.contains('quantity-minus')) { // Updated class name
                handleQuantityChange(productId, -1); // Decrement quantity
            } else if (relevantButton.classList.contains('remove-item')) { // Updated class name
                removeItemFromCart(productId); // Remove item
            }
        }
    });

    // Initial display update when the page loads
    updateCartDisplay();
    // Initial calculation of totals (in case there's something in cart from localStorage)
    calculateTotals();
    updateChangeDue(); // Initial update for change due display
});