<?php
// On essaie avec ton mot de passe perso
$password = "12345678"; 

// Si on est chez un pote qui n'a pas de mot de passe, on peut tester ça :
// $password = ""; 

$conn = new mysqli("localhost", "root", $password, "cinema");

if ($conn->connect_error) {
    // Si ça échoue avec ton pass, on tente sans pass (pour tes potes)
    $conn = new mysqli("localhost", "root", "", "cinema");
    
    if ($conn->connect_error) {
        die("Erreur critique de connexion : " . $conn->connect_error);
    }
}
?>