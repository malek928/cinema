<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// Vérification de la session (Member 2)
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/auth.php");
    exit;
}

// Récupération des films (Note: On utilise $pdo car c'est l'objet de ton collègue)
try {
    $stmt = $pdo->query("SELECT * FROM film ORDER BY id DESC");
    $films = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $films = []; // Évite une erreur si la table est vide ou absente
}

$firstName = isset($_SESSION["nom"]) ? $_SESSION["nom"] : "Invité";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax — Films</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold: #c9a84c;
            --gold-light: #e8c96d;
            --dark: #0a0a0f;
            --dark2: #12121a;
            --dark3: #1c1c28;
            --dark4: #252535;
            --text: #f0ede6;
            --text-muted: #8a8799;
            --red: #e63946;
            --green: #28a745;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--dark);
            color: var(--text);
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 50%, rgba(201,168,76,0.06) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 50%, rgba(230,57,70,0.04) 0%, transparent 60%);
            pointer-events: none;
            z-index: 0;
        }

        /* Film strip animation */
        .filmstrip {
            width: 100%; height: 36px; background: var(--dark2); border-bottom: 2px solid var(--gold);
            display: flex; align-items: center; overflow: hidden; position: relative; z-index: 10;
        }
        .filmstrip-holes { display: flex; gap: 18px; padding: 0 12px; animation: scroll-strip 12s linear infinite; }
        .hole { width: 18px; height: 20px; background: var(--dark); border-radius: 3px; flex-shrink: 0; border: 1px solid #2a2a3a; }
        @keyframes scroll-strip { from { transform: translateX(0); } to { transform: translateX(-300px); } }

        /* Navbar */
        nav {
            position: relative; z-index: 10; display: flex; justify-content: space-between;
            align-items: center; padding: 18px 48px; background: var(--dark2); border-bottom: 1px solid #2a2a3a;
        }
        .logo { font-family: 'Playfair Display', serif; font-size: 26px; font-weight: 700; color: var(--gold); letter-spacing: 4px; text-transform: uppercase; text-decoration: none; }
        .logo span { color: var(--red); }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .nav-links a { color: var(--gold); text-decoration: none; font-size: 14px; margin-right: 15px; font-weight: 500; }
        .welcome { font-size: 14px; color: var(--text-muted); }
        .logout-btn { padding: 8px 20px; background: transparent; border: 1px solid var(--red); color: var(--red); border-radius: 6px; text-decoration: none; font-size: 13px; transition: 0.2s; }
        .logout-btn:hover { background: rgba(230,57,70,0.1); }

        /* Alerts */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 24px; text-align: center; animation: fadeIn 0.5s ease-out; }
        .alert-success { background: rgba(40, 167, 69, 0.2); border: 1px solid var(--green); color: #85ff9e; }
        .alert-error { background: rgba(230, 57, 70, 0.2); border: 1px solid var(--red); color: #ff8585; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* Main */
        main { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 48px 24px; }
        .section-title { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--text); margin-bottom: 8px; }
        .section-title span { color: var(--gold); }
        .section-sub { font-size: 14px; color: var(--text-muted); margin-bottom: 36px; }

        /* Grid */
        .movie-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px; }
        .movie-card { background: var(--dark3); border: 1px solid #2a2a3a; border-radius: 12px; overflow: hidden; transition: 0.25s; }
        .movie-card:hover { transform: translateY(-6px); border-color: var(--gold); box-shadow: 0 12px 40px rgba(201,168,76,0.12); }
        .poster { width: 100%; height: 300px; object-fit: cover; display: block; background: var(--dark4); }
        .info { padding: 16px; }
        .movie-title { font-family: 'Playfair Display', serif; font-size: 17px; color: var(--text); margin-bottom: 6px; }
        .movie-meta { font-size: 11px; color: var(--text-muted); margin-bottom: 14px; display: flex; gap: 8px; }
        .badge { background: var(--dark4); border: 1px solid #2e2e42; padding: 2px 8px; border-radius: 4px; }
        .btn-book { display: block; background: linear-gradient(135deg, var(--gold), var(--gold-light)); color: var(--dark); text-align: center; padding: 10px; text-decoration: none; border-radius: 7px; font-weight: 700; font-size: 13px; }
    </style>
</head>
<body>

<div class="filmstrip">
    <div class="filmstrip-holes">
        <?php for($i=0;$i<30;$i++): ?><div class="hole"></div><?php endfor; ?>
    </div>
</div>

<nav>
    <a href="liste.php" class="logo">Ciné<span>Max</span></a>
    <div class="nav-right">
        <a href="../reservations/statistiques.php" style="color: var(--gold); text-decoration: none; font-size: 14px; margin-right: 20px;">📊 Voir les Stats</a>
        
        <span class="welcome">Bonjour, <strong><?= htmlspecialchars($firstName) ?></strong> 🎬</span>
        <a href="../auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✨ Réservation confirmée ! Vos places sont réservées.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <h1 class="section-title">Nos <span>Films</span></h1>
    <p class="section-sub">Sélectionnez un film pour voir les séances disponibles</p>

    <div class="movie-grid">
        <?php if (empty($films)): ?>
            <p>Aucun film à l'affiche pour le moment.</p>
        <?php else: ?>
            <?php foreach ($films as $film): ?>
                <div class="movie-card">
                    <img src="../assets/<?= htmlspecialchars($film['affiche']) ?>" class="poster" onerror="this.src='https://via.placeholder.com/220x300?text=Affiche+indisponible'">
                    <div class="info">
                        <h3 class="movie-title"><?= htmlspecialchars($film['titre']) ?></h3>
                        <div class="movie-meta">
                            <span class="badge"><?= htmlspecialchars($film['genre']) ?></span>
                            <span class="badge"><?= $film['duree'] ?> min</span>
                        </div>
                        <a href="../reservations/formulaire.php?film_id=<?= $film['id'] ?>" class="btn-book">🎟️ Réserver</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

</body>
</html>