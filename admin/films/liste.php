<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: /cinema/auth/auth.php");
    exit;
}

// Supprimer un film
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    // Supprimer l'affiche du disque
    $stmt = $pdo->prepare("SELECT affiche FROM film WHERE id = ?");
    $stmt->execute([$id]);
    $film = $stmt->fetch();
    if ($film && !empty($film["affiche"])) {
        $path = __DIR__ . "/../../" . $film["affiche"];
        if (file_exists($path)) unlink($path);
    }
    $pdo->prepare("DELETE FROM film WHERE id = ?")->execute([$id]);
    header("Location: /cinema/admin/films/liste.php?deleted=1");
    exit;
}

$films = $pdo->query("SELECT * FROM film ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Gestion Films</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --gold: #c9a84c; --gold-light: #e8c96d;
            --dark: #0a0a0f; --dark2: #12121a; --dark3: #1c1c28; --dark4: #252535;
            --text: #f0ede6; --text-muted: #8a8799; --red: #e63946;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--dark); color: var(--text); min-height: 100vh; }
        body::before {
            content: ''; position: fixed; inset: 0;
            background: radial-gradient(ellipse 80% 60% at 20% 50%, rgba(201,168,76,0.06) 0%, transparent 60%);
            pointer-events: none; z-index: 0;
        }
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

        main { position:relative; z-index:1; max-width:1100px; margin:0 auto; padding:40px 24px; }

        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
        .page-title { font-family:'Playfair Display',serif; font-size:30px; }
        .page-title span { color:var(--gold); }

        .btn-add { display:inline-flex; align-items:center; gap:8px; padding:11px 24px; background:linear-gradient(135deg,var(--gold),var(--gold-light)); color:var(--dark); border-radius:8px; text-decoration:none; font-weight:700; font-size:13px; letter-spacing:0.5px; transition:opacity 0.2s; }
        .btn-add:hover { opacity:0.85; }

        .msg { padding:12px 16px; border-radius:8px; font-size:14px; margin-bottom:20px; }
        .msg-success { background:rgba(42,157,143,0.12); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; }

        /* Table */
        .table-wrap { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        thead { background:var(--dark3); }
        thead th { padding:14px 18px; text-align:left; font-size:11px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); border-bottom:1px solid #2a2a3a; }
        tbody tr { border-bottom:1px solid #1e1e2a; transition:background 0.15s; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background:var(--dark3); }
        td { padding:14px 18px; font-size:14px; vertical-align:middle; }

        .poster-thumb { width:48px; height:64px; object-fit:cover; border-radius:6px; border:1px solid #2a2a3a; }
        .poster-placeholder { width:48px; height:64px; background:var(--dark4); border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:20px; border:1px solid #2a2a3a; }

        .film-title { font-weight:500; color:var(--text); }
        .film-meta { font-size:12px; color:var(--text-muted); margin-top:2px; }

        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; background:var(--dark4); border:1px solid #2e2e42; color:var(--text-muted); }

        .actions { display:flex; gap:8px; }
        .btn-edit { padding:6px 16px; background:transparent; border:1px solid var(--gold); color:var(--gold); border-radius:6px; text-decoration:none; font-size:12px; font-weight:500; transition:background 0.2s; }
        .btn-edit:hover { background:rgba(201,168,76,0.1); }
        .btn-delete { padding:6px 16px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:12px; font-weight:500; transition:background 0.2s; cursor:pointer; }
        .btn-delete:hover { background:rgba(230,57,70,0.1); }

        .empty { text-align:center; padding:60px 20px; color:var(--text-muted); }
        .empty-icon { font-size:48px; margin-bottom:12px; }

        @media(max-width:700px) { nav { padding:14px 20px; } td:nth-child(3), td:nth-child(4) { display:none; } }
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
        <a href="/cinema/admin/films/liste.php" class="nav-link active">🎬 Films</a>
        <a href="/cinema/admin/seances/liste.php" class="nav-link">🕐 Séances</a>
        <a href="/cinema/admin/reservations/liste.php" class="nav-link">🎟️ Réservations</a>
        <a href="/cinema/admin/stats.php" class="nav-link">📊 Stats</a>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <div class="top-bar">
        <h1 class="page-title">Gestion des <span>Films</span></h1>
        <a href="/cinema/admin/films/add.php" class="btn-add">＋ Ajouter un film</a>
    </div>

    <?php if (isset($_GET["deleted"])): ?>
        <div class="msg msg-success">Film supprimé avec succès.</div>
    <?php endif; ?>

    <div class="table-wrap">
        <?php if (empty($films)): ?>
            <div class="empty">
                <div class="empty-icon">🎬</div>
                <p>Aucun film. <a href="/cinema/admin/films/add.php" style="color:var(--gold)">Ajoutez le premier !</a></p>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Affiche</th>
                    <th>Film</th>
                    <th>Genre</th>
                    <th>Année</th>
                    <th>Durée</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($films as $film): ?>
                <tr>
                    <td>
                        <?php if (!empty($film["affiche"])): ?>
                            <img src="/cinema/<?= htmlspecialchars($film["affiche"]) ?>" class="poster-thumb" alt="">
                        <?php else: ?>
                            <div class="poster-placeholder">🎬</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="film-title"><?= htmlspecialchars($film["titre"]) ?></div>
                        <div class="film-meta"><?= mb_substr(htmlspecialchars($film["synopsis"] ?? ""), 0, 60) ?>...</div>
                    </td>
                    <td><span class="badge"><?= htmlspecialchars($film["genre"] ?? "—") ?></span></td>
                    <td><?= $film["annee"] ?? "—" ?></td>
                    <td><?= $film["duree"] ? $film["duree"]." min" : "—" ?></td>
                    <td>
                        <div class="actions">
                            <a href="/cinema/admin/films/edit.php?id=<?= $film["id"] ?>" class="btn-edit">✏️ Modifier</a>
                            <a href="?delete=<?= $film["id"] ?>" class="btn-delete"
                               onclick="return confirm('Supprimer ce film ?')">🗑️ Supprimer</a>
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