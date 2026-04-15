<?php
session_start();
// Fausse session : On fait comme si l'utilisateur John Doe (ID 1) était connecté
$_SESSION['user_id'] = 1; 

// Connexion à TA base
require_once '../config/db.php';

// Récupérer les séances disponibles avec le titre du film
$sql = "SELECT seance.id, film.titre, seance.date, seance.heure, seance.places_dispo 
        FROM seance 
        JOIN film ON seance.id_film = film.id 
        WHERE seance.places_dispo > 0";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réserver une séance</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .form-container { background: #f4f4f4; padding: 20px; border-radius: 8px; max-width: 400px; }
        select, input, button { width: 100%; margin-top: 10px; padding: 8px; }
        button { background: #007BFF; color: white; border: none; cursor: pointer; margin-top: 20px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

    <h2>🎟️ Réserver vos places de Cinéma</h2>

    <div class="form-container">
        <form action="traitement_reservation.php" method="POST">
            
            <label for="id_seance">Choisissez votre séance :</label>
            <select name="id_seance" id="id_seance" required>
                <option value="">-- Sélectionner une séance --</option>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>";
                        // Affichage propre ex: "Batman - 2026-04-20 à 20:30:00"
                        echo htmlspecialchars($row['titre']) . " - " . $row['date'] . " à " . $row['heure'] . " (" . $row['places_dispo'] . " places)";
                        echo "</option>";
                    }
                } else {
                    echo "<option value=''>Aucune séance disponible</option>";
                }
                ?>
            </select>

            <label style="margin-top: 15px; display: block;" for="nb_places">Nombre de places :</label>
            <input type="number" name="nb_places" id="nb_places" min="1" max="10" value="1" required>

            <button type="submit">Valider la réservation</button>
        </form>
    </div>

</body>
</html>