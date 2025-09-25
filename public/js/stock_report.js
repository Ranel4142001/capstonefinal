// ========================
// Print Stock Report
// ========================
function printReportInNewWindow() {
    const printContents = document.getElementById('printableArea').innerHTML;

    // Open a new window for printing
    const printWindow = window.open('', '_blank', 'height=800,width=1000');

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Stock Report Print</title>

            <!-- External Stylesheets -->
            <link rel="stylesheet" href="../public/css/style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

            <!-- Print Styles -->
            <style>
                @media print {
                    .no-print { display: none !important; }
                    @page { size: landscape; margin: 20mm; }
                    body { counter-reset: page; }
                    footer {
                        position: fixed;
                        bottom: 0;
                        width: 100%;
                        text-align: center;
                        font-size: 9pt;
                    }
                    footer::after {
                        content: "Page " counter(page) " of " counter(pages);
                    }
                }
                body { font-size: 10pt; }
                table { width: 100%; border-collapse: collapse; }
                table, th, td { border: 1px solid black; padding: 8px; }
                .print-header { text-align: center; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h3>Stock Report</h3>
                <p>Date Generated: ${new Date().toLocaleString()}</p>
            </div>
            ${printContents}
            <footer></footer>
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    // Wait for styles to load before printing
    printWindow.onload = function () {
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    };
}

// ========================
// Auto-close Bootstrap Alerts
// ========================
function autoCloseAlerts(timeout = 5000) {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bootstrapAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bootstrapAlert.close();
        }, timeout);
    });
}

// Initialize alert auto-close on page load
document.addEventListener('DOMContentLoaded', () => {
    autoCloseAlerts();
});
