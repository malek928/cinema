<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: /cinema/auth/auth.php");
    exit;
}

// Annuler une réservation
if (isset($_GET["annuler"])) {
    $id = (int)$_GET["annuler"];

    // Vérifier que c'est bien la réservation de cet utilisateur
    $check = $pdo->prepare("SELECT r.*, s.date FROM reservation r JOIN seance s ON r.id_seance = s.id WHERE r.id = ? AND r.id_user = ?");
    $check->execute([$id, $_SESSION["user_id"]]);
    $resa = $check->fetch();

    if ($resa && $resa["statut"] !== "annulé") {
        try {
            $pdo->beginTransaction();
            // Remettre les places
            $pdo->prepare("UPDATE seance SET places_dispo = places_dispo + ? WHERE id = ?")
                ->execute([$resa["nb_places"], $resa["id_seance"]]);
            // Annuler
            $pdo->prepare("UPDATE reservation SET statut = 'annulé' WHERE id = ?")
                ->execute([$id]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: /cinema/reservations/liste.php?annule=1");
    exit;
}

// Récupérer les réservations de l'utilisateur
$stmt = $pdo->prepare("
    SELECT r.*, s.date, s.heure, s.salle, s.places_total,
           f.titre, f.affiche, f.genre
    FROM reservation r
    JOIN seance s ON r.id_seance = s.id
    JOIN film f ON s.id_film = f.id
    WHERE r.id_user = ?
    ORDER BY r.date_resa DESC
");
$stmt->execute([$_SESSION["user_id"]]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$firstName = $_SESSION["nom"] ?? "Invité";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Mes Réservations</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --gold: #c9a84c; --gold-light: #e8c96d;
            --dark: #0a0a0f; --dark2: #12121a; --dark3: #1c1c28; --dark4: #252535;
            --text: #f0ede6; --text-muted: #8a8799; --red: #e63946;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--dark); color: var(--text); min-height: 100vh; }
        body::before { content:''; position:fixed; inset:0; background:radial-gradient(ellipse 80% 60% at 20% 50%, rgba(201,168,76,0.06) 0%, transparent 60%); pointer-events:none; z-index:0; }

        .filmstrip { width:100%; height:36px; background:var(--dark2); border-bottom:2px solid var(--gold); display:flex; align-items:center; overflow:hidden; position:relative; z-index:10; }
        .filmstrip-holes { display:flex; gap:18px; padding:0 12px; animation:scroll-strip 12s linear infinite; }
        .hole { width:18px; height:20px; background:var(--dark); border-radius:3px; flex-shrink:0; border:1px solid #2a2a3a; }
        @keyframes scroll-strip { from{transform:translateX(0)} to{transform:translateX(-300px)} }

        nav { position:relative; z-index:10; display:flex; justify-content:space-between; align-items:center; padding:18px 48px; background:var(--dark2); border-bottom:1px solid #2a2a3a; flex-wrap:wrap; gap:12px; }
        .logo { font-family:'Playfair Display',serif; font-size:26px; font-weight:700; color:var(--gold); letter-spacing:4px; text-transform:uppercase; text-decoration:none; }
        .logo span { color:var(--red); }
        .nav-right { display:flex; align-items:center; gap:16px; flex-wrap:wrap; }
        .nav-link { color:var(--text-muted); text-decoration:none; font-size:14px; padding:6px 14px; border-radius:6px; transition:background 0.2s; }
        .nav-link:hover, .nav-link.active { background:var(--dark3); color:var(--text); }
        .welcome { font-size:14px; color:var(--text-muted); }
        .welcome strong { color:var(--gold); }
        .logout-btn { padding:8px 20px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:13px; transition:background 0.2s; }
        .logout-btn:hover { background:rgba(230,57,70,0.1); }

        main { position:relative; z-index:1; max-width:900px; margin:0 auto; padding:40px 24px; }

        .page-title { font-family:'Playfair Display',serif; font-size:30px; margin-bottom:6px; }
        .page-title span { color:var(--gold); }
        .page-sub { font-size:14px; color:var(--text-muted); margin-bottom:28px; }

        .msg { padding:12px 16px; border-radius:8px; font-size:14px; margin-bottom:20px; }
        .msg-success { background:rgba(42,157,143,0.12); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; }

        /* Cards réservations */
        .resa-list { display:flex; flex-direction:column; gap:16px; }

        .resa-card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; overflow:hidden; display:flex; transition:border-color 0.2s; }
        .resa-card:hover { border-color:#3a3a4a; }
        .resa-card.annule { opacity:0.55; }

        .resa-poster { width:80px; flex-shrink:0; }
        .resa-poster img { width:80px; height:110px; object-fit:cover; display:block; }
        .resa-poster-placeholder { width:80px; height:110px; background:var(--dark4); display:flex; align-items:center; justify-content:center; font-size:28px; }

        .resa-body { flex:1; padding:18px 20px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }

        .resa-main { flex:1; }
        .resa-titre { font-family:'Playfair Display',serif; font-size:18px; font-weight:700; margin-bottom:6px; }
        .resa-meta { font-size:13px; color:var(--text-muted); display:flex; gap:12px; flex-wrap:wrap; margin-bottom:10px; }
        .resa-meta span { display:flex; align-items:center; gap:4px; }

        .resa-places { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; background:rgba(201,168,76,0.1); border:1px solid rgba(201,168,76,0.25); border-radius:20px; font-size:13px; color:var(--gold); font-weight:500; }

        .resa-right { text-align:right; display:flex; flex-direction:column; align-items:flex-end; gap:10px; }
        .resa-date { font-size:12px; color:var(--text-muted); }

        /* Badges statut */
        .statut { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:500; }
        .statut-confirme { background:rgba(42,157,143,0.15); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; }
        .statut-annule { background:rgba(230,57,70,0.12); border:1px solid rgba(230,57,70,0.3); color:#ff6b74; }
        .statut-attente { background:rgba(201,168,76,0.12); border:1px solid rgba(201,168,76,0.3); color:var(--gold); }

        .btn-annuler { padding:6px 16px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:12px; transition:background 0.2s; white-space:nowrap; }
        .btn-annuler:hover { background:rgba(230,57,70,0.1); }

        /* Empty */
        .empty { text-align:center; padding:80px 20px; color:var(--text-muted); }
        .empty-icon { font-size:56px; margin-bottom:16px; }
        .empty h3 { font-family:'Playfair Display',serif; font-size:22px; color:var(--text); margin-bottom:8px; }
        .btn-browse { display:inline-block; margin-top:16px; padding:12px 28px; background:linear-gradient(135deg,var(--gold),var(--gold-light)); color:var(--dark); border-radius:8px; text-decoration:none; font-weight:700; font-size:14px; }

        @media(max-width:600px) { nav{padding:14px 20px} .resa-card{flex-direction:column} .resa-poster img{width:100%;height:160px} .resa-poster{width:100%} }
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
        <a href="/cinema/films/liste.php" class="nav-link">🎬 Films</a>
        <a href="/cinema/reservations/liste.php" class="nav-link active">🎟️ Mes réservations</a>
        <span class="welcome">Bonjour, <strong><?= htmlspecialchars($firstName) ?></strong></span>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <h1 class="page-title">Mes <span>Réservations</span></h1>
    <p class="page-sub">Historique de toutes vos réservations</p>

    <?php if (isset($_GET["annule"])): ?>
        <div class="msg msg-success">Réservation annulée. Les places ont été remises en disponibilité.</div>
    <?php endif; ?>

    <?php if (empty($reservations)): ?>
        <div class="empty">
            <div class="empty-icon">🎟️</div>
            <h3>Aucune réservation</h3>
            <p>Vous n'avez pas encore réservé de places.</p>
            <a href="/cinema/films/liste.php" class="btn-browse">Voir les films</a>
        </div>
    <?php else: ?>
        <div class="resa-list">
            <?php foreach ($reservations as $r): ?>
            <?php
                $passe = strtotime($r["date"]) < strtotime(date("Y-m-d"));
                $annule = $r["statut"] === "annulé";
            ?>
            <div class="resa-card <?= $annule ? 'annule' : '' ?>">
                <div class="resa-poster">
                    <?php if (!empty($r["affiche"])): ?>
                        <img src="/cinema/<?= htmlspecialchars($r["affiche"]) ?>" alt="">
                    <?php else: ?>
                        <div class="resa-poster-placeholder">🎬</div>
                    <?php endif; ?>
                </div>
                <div class="resa-body">
                    <div class="resa-main">
                        <div class="resa-titre"><?= htmlspecialchars($r["titre"]) ?></div>
                        <div class="resa-meta">
                            <span>📅 <?= date("d/m/Y", strtotime($r["date"])) ?></span>
                            <span>🕐 <?= substr($r["heure"], 0, 5) ?></span>
                            <span>📍 <?= htmlspecialchars($r["salle"]) ?></span>
                        </div>
                        <div class="resa-places">🎟️ <?= $r["nb_places"] ?> place(s)</div>
                    </div>
                    <div class="resa-right">
                        <div class="resa-date">Réservé le <?= date("d/m/Y", strtotime($r["date_resa"])) ?></div>

                        <?php if ($r["statut"] === "confirmé"): ?>
                            <span class="statut statut-confirme">✓ Confirmé</span>
                        <?php elseif ($r["statut"] === "annulé"): ?>
                            <span class="statut statut-annule">✗ Annulé</span>
                        <?php else: ?>
                            <span class="statut statut-attente">⏳ En attente</span>
                        <?php endif; ?>

                        <?php if (!$annule && !$passe): ?>
                            <a href="?annuler=<?= $r["id"] ?>" class="btn-annuler"
                               onclick="return confirm('Annuler cette réservation ?')">Annuler</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>