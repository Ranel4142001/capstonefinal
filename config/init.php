<?php
// capstonefinal/config/init.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Any other global initializations