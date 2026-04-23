<?php
// On active l'affichage des erreurs pour MySQL (indispensable pour débugger tes transactions)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$password = ""; // Mot de passe vide par défaut sur XAMPP
$database = "cinema"; // Nom de ta base selon ton fichier

try {
    $conn = new mysqli($host, $user, $password, $database);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("❌ Erreur critique de connexion : " . $e->getMessage());
}
?>