<?php
require_once '../config/db.php';

// 1. Récupérer l'ID du film depuis l'URL (envoyé par liste.php)
$film_id = isset($_GET['film_id']) ? intval($_GET['film_id']) : 0;

// 2. Récupérer les infos du film pour l'affichage
$film_titre = "votre film";
if ($film_id > 0) {
    $stmt_film = $conn->prepare("SELECT titre FROM film WHERE id = ?");
    $stmt_film->bind_param("i", $film_id);
    $stmt_film->execute();
    $res_film = $stmt_film->get_result();
    if ($f = $res_film->fetch_assoc()) {
        $film_titre = $f['titre'];
    }
}

// 3. Récupérer les séances UNIQUEMENT pour ce film
try {
    $stmt_seances = $conn->prepare("SELECT id, date_heure, places_dispo FROM seances WHERE film_id = ? AND places_dispo > 0");
    $stmt_seances->bind_param("i", $film_id);
    $stmt_seances->execute();
    $resultats = $stmt_seances->get_result();
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver - <?= htmlspecialchars($film_titre) ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #0a0a0f; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: #1c1c28; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); width: 100%; max-width: 400px; border: 1px solid #c9a84c; }
        h2 { color: #c9a84c; text-align: center; margin-bottom: 10px; }
        .film-name { text-align: center; color: #8a8799; margin-bottom: 25px; font-style: italic; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #f0ede6; }
        select, input, button { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #2a2a3a; border-radius: 6px; background: #0a0a0f; color: white; }
        button { background-color: #c9a84c; color: #0a0a0f; border: none; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background-color: #e8c96d; }
        .error-msg { background: rgba(230, 57, 70, 0.2); color: #ff8585; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <h2>🎟️ Réservation</h2>
    <p class="film-name"><?= htmlspecialchars($film_titre) ?></p>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="reserver.php" method="POST">
        <input type="hidden" name="film_id" value="<?= $film_id ?>">
        
        <label for="seance_id">Choisir une séance :</label>
        <select name="seance_id" id="seance_id" required>
            <?php if ($resultats && $resultats->num_rows > 0): ?>
                <option value="">-- Sélectionnez un horaire --</option>
                <?php while($row = $resultats->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>">
                        <?= date('d/m à H:i', strtotime($row['date_heure'])) ?> 
                        (<?= $row['places_dispo'] ?> places)
                    </option>
                <?php endwhile; ?>
            <?php else: ?>
                <option value="">Aucune séance pour ce film</option>
            <?php endif; ?>
        </select>

        <label for="nb_places">Nombre de places :</label>
        <input type="number" name="nb_places" id="nb_places" min="1" max="10" value="1" required>

        <button type="submit">Confirmer la réservation</button>
        <a href="../films/liste.php" style="display:block; text-align:center; color:#8a8799; text-decoration:none; font-size:13px;">Retour aux films</a>
    </form>
</div>

</body>
</html>