<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// Sécurité : si pas connecté, retour au login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Récupération des films
$stmt = $pdo->query("SELECT * FROM film ORDER BY id DESC");
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compatibilité PHP 5.6 pour le prénom
$firstName = isset($_SESSION["prenom"]) ? $_SESSION["prenom"] : "Guest";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Movie Catalog</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #141414;
            color: white;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e50914;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .logout-btn {
            background: #e50914;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
        }

        .movie-card {
            background: #262626;
            border-radius: 10px;
            overflow: hidden;
            transition: 0.3s;
            border: 1px solid #333;
        }

        .movie-card:hover {
            transform: scale(1.03);
        }

        /* Chemin vers tes images dans cinema/assets/ */
        .poster {
            width: 100%;
            height: 350px;
            object-fit: cover;
            display: block;
            background-color: #333;
        }

        .info {
            padding: 15px;
        }

        .movie-title {
            font-size: 1.2em;
            margin: 0;
            color: #fff;
        }

        .movie-meta {
            color: #888;
            font-size: 0.9em;
            margin: 5px 0;
        }

        .btn-book {
            display: block;
            background: #e50914;
            color: white;
            text-align: center;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($firstName); ?>! 🍿</h1>
        <a href="../auth/logout.php" class="logout-btn">Sign Out</a>
    </div>

    <div class="movie-grid">
        <?php foreach ($films as $film): ?>
            <div class="movie-card">
                <img src="../assets/<?php echo htmlspecialchars($film['affiche']); ?>" alt="Poster" class="poster">

                <div class="info">
                    <h3 class="movie-title"><?php echo htmlspecialchars($film['titre']); ?></h3>
                    <div class="movie-meta"><?php echo htmlspecialchars($film['genre']); ?> | <?php echo $film['annee']; ?></div>
                    <a href="../seances/liste.php?id=<?php echo $film['id']; ?>" class="btn-book">Get Tickets</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>

</html>