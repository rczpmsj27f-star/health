<?php
session_start();
// Clear header session variables
unset($_SESSION['header_display_name']);
unset($_SESSION['header_avatar_url']);
// Destroy entire session
session_unset();
session_destroy();
header("Location: /login.php");
exit;
