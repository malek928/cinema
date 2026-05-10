<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: /cinema/auth/auth.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM film ORDER BY id DESC");
$films = $stmt->fetchAll(PDO::FETCH_ASSOC);
$firstName = isset($_SESSION["nom"]) ? $_SESSION["nom"] : "Invité";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax — Films</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
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

        /* Filmstrip */
        .filmstrip {
            width: 100%; height: 36px;
            background: var(--dark2);
            border-bottom: 2px solid var(--gold);
            display: flex; align-items: center;
            overflow: hidden; position: relative; z-index: 10;
        }
        .filmstrip-holes {
            display: flex; gap: 18px; padding: 0 12px;
            animation: scroll-strip 12s linear infinite;
        }
        .hole {
            width: 18px; height: 20px;
            background: var(--dark); border-radius: 3px;
            flex-shrink: 0; border: 1px solid #2a2a3a;
        }
        @keyframes scroll-strip {
            from { transform: translateX(0); }
            to   { transform: translateX(-300px); }
        }

        /* Navbar */
        nav {
            position: relative; z-index: 10;
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 48px;
            background: var(--dark2);
            border-bottom: 1px solid #2a2a3a;
            gap: 16px; flex-wrap: wrap;
        }
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 26px; font-weight: 700;
            color: var(--gold); letter-spacing: 4px;
            text-transform: uppercase; text-decoration: none;
        }
        .logo span { color: var(--red); }
        .nav-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .welcome { font-size: 14px; color: var(--text-muted); margin-right: 8px; }
        .welcome strong { color: var(--gold); }
        .nav-btn {
            padding: 8px 16px;
            background: var(--dark3);
            border: 1px solid #2a2a3a;
            color: var(--text-muted);
            border-radius: 6px; text-decoration: none;
            font-size: 13px; font-weight: 500;
            transition: border-color 0.2s, color 0.2s;
        }
        .nav-btn:hover { border-color: var(--gold); color: var(--gold); }
        .logout-btn {
            padding: 8px 20px; background: transparent;
            border: 1px solid var(--red); color: var(--red);
            border-radius: 6px; text-decoration: none;
            font-size: 13px; font-weight: 500; transition: background 0.2s;
        }
        .logout-btn:hover { background: rgba(230,57,70,0.1); }

        /* Main */
        main {
            position: relative; z-index: 1;
            max-width: 1200px; margin: 0 auto;
            padding: 48px 24px;
        }

        .section-header { margin-bottom: 36px; }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px; color: var(--text); margin-bottom: 6px;
        }
        .section-title span { color: var(--gold); }
        .section-sub { font-size: 14px; color: var(--text-muted); }

        /* ═══════════════════════════════════════
           MOVIE GRID + HOVER EFFECT
        ═══════════════════════════════════════ */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 28px;
        }

        .movie-card {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            aspect-ratio: 2/3;
            background: var(--dark3);
            border: 1px solid #2a2a3a;
            /* Légère élévation au départ */
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                        box-shadow 0.4s ease,
                        border-color 0.4s ease;
        }

        .movie-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 24px 60px rgba(0,0,0,0.6), 0 0 0 1px rgba(201,168,76,0.3);
            border-color: var(--gold);
        }

        /* Affiche */
        .poster {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                        filter 0.4s ease;
        }

        .movie-card:hover .poster {
            transform: scale(1.08);
            filter: brightness(0.35) saturate(0.8);
        }

        .poster-placeholder {
            width: 100%; height: 100%;
            background: var(--dark4);
            display: flex; align-items: center; justify-content: center;
            font-size: 64px;
            transition: filter 0.4s ease;
        }

        .movie-card:hover .poster-placeholder {
            filter: brightness(0.3);
        }

        /* ── Overlay info (apparaît au hover) ── */
        .card-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 24px 20px 20px;
            /* Gradient du bas vers le haut */
            background: linear-gradient(
                to top,
                rgba(0,0,0,0.95) 0%,
                rgba(0,0,0,0.7) 40%,
                transparent 100%
            );
            opacity: 0;
            transform: translateY(12px);
            transition: opacity 0.35s ease, transform 0.35s ease;
            pointer-events: none;
        }

        .movie-card:hover .card-overlay {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        /* Titre toujours visible en bas */
        .card-always {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            padding: 36px 16px 16px;
            background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 100%);
            transition: opacity 0.3s ease;
        }

        .movie-card:hover .card-always {
            opacity: 0;
        }

        .always-title {
            font-family: 'Playfair Display', serif;
            font-size: 16px; font-weight: 700;
            color: var(--text); margin-bottom: 4px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }

        .always-badges { display: flex; gap: 6px; flex-wrap: wrap; }
        .badge-sm {
            font-size: 10px; padding: 2px 7px;
            border-radius: 3px;
            background: rgba(201,168,76,0.2);
            border: 1px solid rgba(201,168,76,0.3);
            color: var(--gold);
        }

        /* Contenu overlay */
        .overlay-genre {
            font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
            color: var(--gold); margin-bottom: 8px; font-weight: 500;
        }

        .overlay-title {
            font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 700; line-height: 1.2;
            color: var(--text); margin-bottom: 10px;
        }

        .overlay-synopsis {
            font-size: 12px; color: rgba(240,237,230,0.75);
            line-height: 1.6; margin-bottom: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .overlay-meta {
            display: flex; gap: 10px; margin-bottom: 16px;
        }

        .meta-pill {
            font-size: 11px; padding: 3px 10px;
            border-radius: 20px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(240,237,230,0.7);
        }

        .btn-reserver {
            display: block; width: 100%;
            padding: 11px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--dark);
            text-align: center; text-decoration: none;
            border-radius: 8px;
            font-weight: 700; font-size: 13px; letter-spacing: 0.5px;
            transition: opacity 0.2s, transform 0.2s;
        }
        .btn-reserver:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Numéro du film (décoratif) */
        .card-number {
            position: absolute;
            top: 14px; right: 14px;
            font-family: 'Playfair Display', serif;
            font-size: 11px; letter-spacing: 2px;
            color: rgba(201,168,76,0.5);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .movie-card:hover .card-number { opacity: 1; }

        /* Empty state */
        .empty {
            text-align: center; padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-icon { font-size: 56px; margin-bottom: 16px; }
        .empty h3 {
            font-family: 'Playfair Display', serif;
            font-size: 22px; color: var(--text); margin-bottom: 8px;
        }

        @media(max-width: 600px) {
            nav { padding: 14px 20px; }
            .movie-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; }
            .overlay-synopsis { display: none; }
        }
    </style>
</head>
<body>

<div class="filmstrip">
    <div class="filmstrip-holes">
        <?php for($i=0;$i<30;$i++): ?><div class="hole"></div><?php endfor; ?>
    </div>
</div>

<nav>
    <a href="/cinema/films/liste.php" class="logo">Ciné<span>Max</span></a>
    <div class="nav-right">
        <span class="welcome">Bonjour, <strong><?= htmlspecialchars($firstName) ?></strong> 🎬</span>
        <a href="/cinema/profil.php" class="nav-btn">👤 Profil</a>
        <a href="/cinema/reservations/liste.php" class="nav-btn">🎟️ Mes réservations</a>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <div class="section-header">
        <h1 class="section-title">Nos <span>Films</span></h1>
        <p class="section-sub">Survolez un film pour découvrir les détails — cliquez pour réserver</p>
    </div>

    <?php if (empty($films)): ?>
        <div class="empty">
            <div class="empty-icon">🎭</div>
            <h3>Aucun film disponible</h3>
            <p>Les films seront bientôt ajoutés.</p>
        </div>
    <?php else: ?>
        <div class="movie-grid">
            <?php foreach ($films as $i => $film): ?>
                <div class="movie-card">

                    <!-- Numéro décoratif -->
                    <span class="card-number"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></span>

                    <!-- Affiche -->
                    <?php if (!empty($film['affiche'])): ?>
                        <img src="/cinema/<?= htmlspecialchars($film['affiche']) ?>"
                             alt="<?= htmlspecialchars($film['titre']) ?>"
                             class="poster">
                    <?php else: ?>
                        <div class="poster-placeholder">🎬</div>
                    <?php endif; ?>

                    <!-- Info toujours visible (disparaît au hover) -->
                    <div class="card-always">
                        <div class="always-title"><?= htmlspecialchars($film['titre']) ?></div>
                        <div class="always-badges">
                            <?php if ($film['genre']): ?>
                                <span class="badge-sm"><?= htmlspecialchars($film['genre']) ?></span>
                            <?php endif; ?>
                            <?php if ($film['annee']): ?>
                                <span class="badge-sm"><?= $film['annee'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Overlay au hover -->
                    <div class="card-overlay">
                        <div class="overlay-genre"><?= htmlspecialchars($film['genre'] ?? '') ?></div>
                        <div class="overlay-title"><?= htmlspecialchars($film['titre']) ?></div>

                        <?php if (!empty($film['synopsis'])): ?>
                            <div class="overlay-synopsis"><?= htmlspecialchars($film['synopsis']) ?></div>
                        <?php endif; ?>

                        <div class="overlay-meta">
                            <?php if ($film['annee']): ?>
                                <span class="meta-pill">📅 <?= $film['annee'] ?></span>
                            <?php endif; ?>
                            <?php if ($film['duree']): ?>
                                <span class="meta-pill">⏱ <?= $film['duree'] ?> min</span>
                            <?php endif; ?>
                        </div>

                        <a href="/cinema/seances/liste.php?id=<?= $film['id'] ?>" class="btn-reserver">
                            🎟️ Voir les séances
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>