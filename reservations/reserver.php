<?php
// 1. Démarrer la session pour récupérer l'ID de l'utilisateur (Member 2)
session_start();

// 2. Connexion à la base de données
// Note : On utilise $conn (MySQLi) ici comme dans ton script initial
require_once '../config/db.php';

// ON VÉRIFIE QUE LE FORMULAIRE A BIEN ÉTÉ ENVOYÉ
if (!isset($_POST['nb_places']) || !isset($_POST['seance_id'])) {
    header("Location: ../films/liste.php");
    exit;
}

// ON RÉCUPÈRE LES VALEURS
$seance_id = $_POST['seance_id']; 

// ON RÉCUPÈRE L'ID DE L'UTILISATEUR CONNECTÉ
// Si la session n'existe pas, on redirige vers l'auth ou on met un ID de test (1)
if (!isset($_SESSION['user_id'])) {
    // Pour tes tests locaux tu peux laisser : $client_id = 1;
    // Mais pour la version finale, mieux vaut rediriger :
    header("Location: ../auth/auth.php");
    exit;
}
$client_id = $_SESSION['user_id']; 
$nb_places_demandees = intval($_POST['nb_places']);

try {
    // 3. DÉMARRER LA TRANSACTION (Sécurité anti-doublons)
    $conn->begin_transaction();

    // 4. VÉRIFIER LES PLACES (FOR UPDATE bloque la ligne en BDD)
    $stmt_check = $conn->prepare("SELECT places_dispo FROM seances WHERE id = ? FOR UPDATE");
    $stmt_check->bind_param("i", $seance_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $seance = $result->fetch_assoc();

    if (!$seance) {
        throw new Exception("Cette séance n'existe pas.");
    }

    if ($seance['places_dispo'] < $nb_places_demandees) {
        throw new Exception("Désolé, il ne reste que " . $seance['places_dispo'] . " places.");
    }

    // 5. INSÉRER LA RÉSERVATION
    $stmt_insert = $conn->prepare("INSERT INTO reservations (seance_id, user_id, nb_places) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("iii", $seance_id, $client_id, $nb_places_demandees);
    $stmt_insert->execute();

    // 6. METTRE À JOUR LE STOCK DE PLACES
    $stmt_update = $conn->prepare("UPDATE seances SET places_dispo = places_dispo - ? WHERE id = ?");
    $stmt_update->bind_param("ii", $nb_places_demandees, $seance_id);
    $stmt_update->execute();

    // 7. VALIDER LA TRANSACTION
    $conn->commit();

    // 8. REDIRECTION VERS LA LISTE AVEC MESSAGE DE SUCCÈS
    header("Location: ../films/liste.php?success=1"); 
    exit;

} catch (Exception $e) {
    // 9. ANNULER TOUT EN CAS D'ERREUR
    $conn->rollback();
    
    // Redirection vers le formulaire avec le message d'erreur
    header("Location: formulaire.php?film_id=" . ($_POST['film_id'] ?? '') . "&error=" . urlencode($e->getMessage()));
    exit;
}
?>