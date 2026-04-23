<?php
// On indique au navigateur que ce fichier renvoie du JSON (pas du HTML)
header('Content-Type: application/json');

// Inclure la connexion à la base de données
require_once '../config/db.php';

try {
    // Requête SQL pour compter le nombre total de places réservées par séance
    // On fait un JOIN pour récupérer la date de la séance en même temps
    $query = "
        SELECT s.date_heure, SUM(r.nb_places) as total_reserve
        FROM seances s
        JOIN reservations r ON s.id = r.seance_id
        GROUP BY s.id
    ";
    
    $result = $conn->query($query);
    
    // On prépare un tableau pour stocker les données
    $data = [];
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = [
                'seance' => $row['date_heure'],
                'reservations' => (int)$row['total_reserve']
            ];
        }
    }
    
    // On transforme notre tableau PHP en format JSON pour Chart.js
    echo json_encode($data);

} catch (Exception $e) {
    // En cas d'erreur, on renvoie une erreur en JSON
    echo json_encode(['error' => $e->getMessage()]);
}
?>