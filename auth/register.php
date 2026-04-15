<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../config/db.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $telephone = $_POST["telephone"];

    $stmt = $pdo->prepare("INSERT INTO user (nom, prenom, email, password, telephone)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $email, $password, $telephone]);

    echo "Compte créé avec succès";
}
?>

<h2>Register</h2>
<form method="POST">
    <input name="nom" placeholder="Nom"><br>
    <input name="prenom" placeholder="Prenom"><br>
    <input name="email" placeholder="Email"><br>
    <input name="telephone" placeholder="Telephone"><br>
    <input type="password" name="password" placeholder="Password"><br>
    <button type="submit">Register</button>
</form>