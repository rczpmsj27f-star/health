<?php
session_start();
// Destroy entire session (clears all session variables including header cache)
session_unset();
session_destroy();
header("Location: /login.php");
exit;
