<?php
session_start();
session_unset();    // Kosongkan semua data session
session_destroy();  // Hancurkan session
header("Location: login.php"); // Arahkan kembali ke halaman login
exit();
?>