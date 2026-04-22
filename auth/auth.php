<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
 
require_once __DIR__ . "/../config/db.php";
session_start();
 
// Si déjà connecté
if (isset($_SESSION["user_id"])) {
    header("Location: ../films/liste.php");
    exit;
}
 
$login_error     = "";
$register_error  = "";
$register_success = "";
 
// ── LOGIN ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["form_type"] == "login") {
 
    $email    = $_POST["email"];
    $password = $_POST["password"];
 
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
 
    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["nom"]     = $user["nom"];
        $_SESSION["role"]    = $user["role"];
 
        header("Location: ../index.php");
        exit;
    } else {
        $login_error = "Email ou mot de passe incorrect.";
    }
}
 
// ── REGISTER ───────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["form_type"] == "register") {
 
    $nom       = $_POST["nom"];
    $prenom    = $_POST["prenom"];
    $email     = $_POST["email"];
    $telephone = $_POST["telephone"];
    $password  = password_hash($_POST["password"], PASSWORD_DEFAULT);
 
    // Vérifier si email existe déjà
    $check = $pdo->prepare("SELECT id FROM user WHERE email = ?");
    $check->execute([$email]);
 
    if ($check->fetch()) {
        $register_error = "Cet email est déjà utilisé.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO user (nom, prenom, email, password, telephone)
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $password, $telephone]);
        $register_success = "Compte créé ! Vous pouvez vous connecter.";
    }
}
 
include "auth.view.php";
?>