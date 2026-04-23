<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Réservations</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f7f6;
            padding: 40px 20px;
            margin: 0;
            text-align: center;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        h2 { color: #333; margin-bottom: 20px; }
    </style>
</head>
<body>

    <h2>📊 Statistiques des Réservations par Séance</h2>
    
    <div class="chart-container">
        <canvas id="graphiqueReservations"></canvas>
    </div>

    <script>
        // 3. On utilise JavaScript (Fetch API) pour lire ton fichier stats_api.php
        fetch('stats_api.php')
            .then(response => response.json()) // On transforme la réponse en JSON lisible par JS
            .then(data => {
                
                // On extrait les dates pour l'axe X, et le nombre de places pour l'axe Y
                const labelsSeances = data.map(item => item.seance); 
                const donneesPlaces = data.map(item => item.reservations); 

                // 4. Configuration de Chart.js
                const ctx = document.getElementById('graphiqueReservations').getContext('2d');
                
                new Chart(ctx, {
                    type: 'bar', // Tu peux changer 'bar' en 'pie' ou 'line' pour tester !
                    data: {
                        labels: labelsSeances, // L'axe X (en bas)
                        datasets: [{
                            label: 'Places réservées',
                            data: donneesPlaces, // L'axe Y (à gauche)
                            backgroundColor: 'rgba(54, 162, 235, 0.5)', // Couleur de remplissage bleu
                            borderColor: 'rgba(54, 162, 235, 1)',       // Couleur des bordures
                            borderWidth: 2,
                            borderRadius: 5 // Petits coins arrondis stylés
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 } // Pour ne pas afficher des demi-places (ex: 2.5)
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Erreur lors du chargement des statistiques :', error);
            });
    </script>

</body>
</html>