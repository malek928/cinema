<?php
session_start();
session_destroy();

header("Location: auth.php");  // ← vérifier que c'est auth.php
exit;
?>