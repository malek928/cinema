<?php
session_start();
require_once __DIR__ . "/../../config/db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: /cinema/auth/auth.php");
    exit;
}
// Changer le statut
if (isset($_GET["statut"]) && isset($_GET["id"])) {
    $id     = (int)$_GET["id"];
    $statut = $_GET["statut"];

    if (in_array($statut, ["confirmé", "annulé", "en attente"])) {
        // Si on annule, remettre les places
        if ($statut === "annulé") {
            $r = $pdo->prepare("SELECT * FROM reservation WHERE id = ? AND statut != 'annulé'");
            $r->execute([$id]);
            $resa = $r->fetch();
            if ($resa) {
                $pdo->prepare("UPDATE seance SET places_dispo = places_dispo + ? WHERE id = ?")
                    ->execute([$resa["nb_places"], $resa["id_seance"]]);
            }
        }
        $pdo->prepare("UPDATE reservation SET statut = ? WHERE id = ?")
            ->execute([$statut, $id]);
    }
    header("Location: /cinema/admin/reservations/liste.php");
    exit;
}

// Récupérer toutes les réservations
$reservations = $pdo->query("
    SELECT r.*, u.nom, u.prenom, u.email,
           s.date, s.heure, s.salle,
           f.titre, f.affiche
    FROM reservation r
    JOIN user u ON r.id_user = u.id
    JOIN seance s ON r.id_seance = s.id
    JOIN film f ON s.id_film = f.id
    ORDER BY r.date_resa DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Réservations Admin</title>
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
        .nav-links { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .nav-link { color:var(--text-muted); text-decoration:none; font-size:14px; padding:6px 14px; border-radius:6px; transition:background 0.2s,color 0.2s; }
        .nav-link:hover { background:var(--dark3); color:var(--text); }
        .nav-link.active { color:var(--gold); border:1px solid rgba(201,168,76,0.3); }
        .logout-btn { padding:8px 20px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:13px; transition:background 0.2s; }
        .logout-btn:hover { background:rgba(230,57,70,0.1); }

        main { position:relative; z-index:1; max-width:1200px; margin:0 auto; padding:40px 24px; }

        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
        .page-title { font-family:'Playfair Display',serif; font-size:30px; }
        .page-title span { color:var(--gold); }

        /* Stats rapides */
        .stats-bar { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:28px; }
        .stat-card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:10px; padding:16px; text-align:center; }
        .stat-num { font-family:'Playfair Display',serif; font-size:28px; color:var(--gold); }
        .stat-label { font-size:12px; color:var(--text-muted); margin-top:4px; }

        /* Table */
        .table-wrap { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; overflow:auto; }
        table { width:100%; border-collapse:collapse; min-width:700px; }
        thead { background:var(--dark3); }
        thead th { padding:13px 16px; text-align:left; font-size:11px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); border-bottom:1px solid #2a2a3a; }
        tbody tr { border-bottom:1px solid #1e1e2a; transition:background 0.15s; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background:var(--dark3); }
        td { padding:13px 16px; font-size:14px; vertical-align:middle; }

        .poster-thumb { width:40px; height:54px; object-fit:cover; border-radius:5px; }
        .poster-ph { width:40px; height:54px; background:var(--dark4); border-radius:5px; display:flex; align-items:center; justify-content:center; font-size:16px; }

        .user-name { font-weight:500; }
        .user-email { font-size:12px; color:var(--text-muted); }

        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
        .badge-green { background:rgba(42,157,143,0.15); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; }
        .badge-red { background:rgba(230,57,70,0.12); border:1px solid rgba(230,57,70,0.3); color:#ff6b74; }
        .badge-gray { background:var(--dark4); border:1px solid #2e2e42; color:var(--text-muted); }

        .actions { display:flex; gap:6px; flex-wrap:wrap; }
        .btn-sm { padding:5px 12px; border-radius:6px; font-size:11px; font-weight:500; text-decoration:none; transition:background 0.2s; white-space:nowrap; }
        .btn-confirm { background:rgba(42,157,143,0.15); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; }
        .btn-confirm:hover { background:rgba(42,157,143,0.3); }
        .btn-cancel { background:rgba(230,57,70,0.12); border:1px solid rgba(230,57,70,0.3); color:#ff6b74; }
        .btn-cancel:hover { background:rgba(230,57,70,0.25); }
        .btn-pending { background:var(--dark4); border:1px solid #2e2e42; color:var(--text-muted); }
        .btn-pending:hover { background:var(--dark3); }

        .empty { text-align:center; padding:50px; color:var(--text-muted); }

        @media(max-width:800px) { .stats-bar { grid-template-columns:repeat(2,1fr); } nav { padding:14px 20px; } }
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
    <div class="nav-links">
        <a href="/cinema/admin/films/liste.php" class="nav-link">🎬 Films</a>
        <a href="/cinema/admin/seances/liste.php" class="nav-link">🕐 Séances</a>
        <a href="/cinema/admin/reservations/liste.php" class="nav-link active">🎟️ Réservations</a>
        <a href="/cinema/admin/stats.php" class="nav-link">📊 Stats</a>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <div class="top-bar">
        <h1 class="page-title">Toutes les <span>Réservations</span></h1>
    </div>

    <!-- Stats rapides -->
    <?php
    $total     = count($reservations);
    $confirmes = count(array_filter($reservations, fn($r) => $r["statut"] === "confirmé"));
    $annules   = count(array_filter($reservations, fn($r) => $r["statut"] === "annulé"));
    $attente   = count(array_filter($reservations, fn($r) => $r["statut"] === "en attente"));
    $places    = array_sum(array_column(array_filter($reservations, fn($r) => $r["statut"] === "confirmé"), "nb_places"));
    ?>
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-num"><?= $total ?></div>
            <div class="stat-label">Total réservations</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#4ecdc4"><?= $confirmes ?></div>
            <div class="stat-label">Confirmées</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#ff6b74"><?= $annules ?></div>
            <div class="stat-label">Annulées</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $places ?></div>
            <div class="stat-label">Places vendues</div>
        </div>
    </div>

    <div class="table-wrap">
        <?php if (empty($reservations)): ?>
            <div class="empty">Aucune réservation pour le moment.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Film</th>
                    <th>Client</th>
                    <th>Séance</th>
                    <th>Places</th>
                    <th>Statut</th>
                    <th>Date résa</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $r):
                    $badge = match($r["statut"]) {
                        "confirmé"   => "badge-green",
                        "annulé"     => "badge-red",
                        default      => "badge-gray"
                    };
                ?>
                <tr>
                    <td style="display:flex;align-items:center;gap:10px">
                        <?php if (!empty($r["affiche"])): ?>
                            <img src="/cinema/<?= htmlspecialchars($r["affiche"]) ?>" class="poster-thumb" alt="">
                        <?php else: ?>
                            <div class="poster-ph">🎬</div>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($r["titre"]) ?></span>
                    </td>
                    <td>
                        <div class="user-name"><?= htmlspecialchars($r["prenom"]." ".$r["nom"]) ?></div>
                        <div class="user-email"><?= htmlspecialchars($r["email"]) ?></div>
                    </td>
                    <td>
                        <?= date("d/m/Y", strtotime($r["date"])) ?><br>
                        <small style="color:var(--text-muted)"><?= substr($r["heure"],0,5) ?> — <?= htmlspecialchars($r["salle"]) ?></small>
                    </td>
                    <td style="text-align:center;font-size:18px;font-weight:600;color:var(--gold)"><?= $r["nb_places"] ?></td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($r["statut"]) ?></span></td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= date("d/m/Y H:i", strtotime($r["date_resa"])) ?></td>
                    <td>
                        <div class="actions">
                            <?php if ($r["statut"] !== "confirmé"): ?>
                                <a href="?id=<?= $r["id"] ?>&statut=confirmé" class="btn-sm btn-confirm">✅ Confirmer</a>
                            <?php endif; ?>
                            <?php if ($r["statut"] !== "en attente"): ?>
                                <a href="?id=<?= $r["id"] ?>&statut=en attente" class="btn-sm btn-pending">⏳ Attente</a>
                            <?php endif; ?>
                            <?php if ($r["statut"] !== "annulé"): ?>
                                <a href="?id=<?= $r["id"] ?>&statut=annulé" class="btn-sm btn-cancel"
                                   onclick="return confirm('Annuler cette réservation ?')">✕ Annuler</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>

</body>
</html>