<?php
session_start();
session_destroy();

header("Location: /cinema/auth/auth.php");
exit;
?>