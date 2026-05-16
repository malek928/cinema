<?php
session_start();
require_once __DIR__ . "/../../config/db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: /cinema/auth/auth.php");
    exit;
}

$error = "";
$success = "";

// Ajouter une séance
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_film      = (int)$_POST["id_film"];
    $date         = $_POST["date"];
    $heure        = $_POST["heure"];
    $salle        = trim($_POST["salle"]);
    $places_total = (int)$_POST["places_total"];

    if ($id_film && $date && $heure && $salle && $places_total > 0) {
        $stmt = $pdo->prepare("INSERT INTO seance (id_film, date, heure, salle, places_total, places_dispo)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_film, $date, $heure, $salle, $places_total, $places_total]);
        $success = "Séance ajoutée avec succès !";
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Supprimer une séance
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $pdo->prepare("DELETE FROM seance WHERE id = ?")->execute([$id]);
    header("Location: /cinema/admin/seances/liste.php?deleted=1");
    exit;
}

// Récupérer toutes les séances avec le nom du film
$seances = $pdo->query("
    SELECT s.*, f.titre, f.affiche
    FROM seance s
    JOIN film f ON s.id_film = f.id
    ORDER BY s.date DESC, s.heure DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les films pour le formulaire
$films = $pdo->query("SELECT id, titre FROM film ORDER BY titre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Gestion Séances</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --gold: #c9a84c; --gold-light: #e8c96d;
            --dark: #0a0a0f; --dark2: #12121a; --dark3: #1c1c28; --dark4: #252535;
            --text: #f0ede6; --text-muted: #8a8799; --red: #e63946; --green: #2a9d8f;
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

        main { position:relative; z-index:1; max-width:1100px; margin:0 auto; padding:40px 24px; }

        .page-title { font-family:'Playfair Display',serif; font-size:30px; margin-bottom:28px; }
        .page-title span { color:var(--gold); }

        /* Layout 2 colonnes */
        .layout { display:grid; grid-template-columns:340px 1fr; gap:28px; align-items:start; }

        /* Formulaire ajout */
        .card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; padding:28px; }
        .card-title { font-size:16px; font-weight:500; color:var(--text); margin-bottom:20px; padding-bottom:12px; border-bottom:1px solid #2a2a3a; }

        .form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
        .form-group label { font-size:11px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); }
        .form-group input,
        .form-group select { padding:10px 14px; background:var(--dark4); border:1px solid #2e2e42; border-radius:8px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:14px; transition:border-color 0.2s; outline:none; }
        .form-group input:focus,
        .form-group select:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,168,76,0.12); }
        .form-group select option { background:var(--dark4); }

        .btn-submit { width:100%; padding:12px; background:linear-gradient(135deg,var(--gold),var(--gold-light)); color:var(--dark); border:none; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:700; letter-spacing:0.5px; cursor:pointer; transition:opacity 0.2s; margin-top:4px; }
        .btn-submit:hover { opacity:0.9; }

        /* Messages */
        .msg { padding:11px 14px; border-radius:8px; font-size:13px; margin-bottom:16px; }
        .msg-error { background:rgba(230,57,70,0.12); border:1px solid rgba(230,57,70,0.3); color:#ff6b74; }
        .msg-success { background:rgba(42,157,143,0.12); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; }

        /* Table séances */
        .table-wrap { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        thead { background:var(--dark3); }
        thead th { padding:13px 16px; text-align:left; font-size:11px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); border-bottom:1px solid #2a2a3a; }
        tbody tr { border-bottom:1px solid #1e1e2a; transition:background 0.15s; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background:var(--dark3); }
        td { padding:13px 16px; font-size:14px; vertical-align:middle; }

        .film-name { font-weight:500; color:var(--text); }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; background:var(--dark4); border:1px solid #2e2e42; color:var(--text-muted); }
        .badge-green { background:rgba(42,157,143,0.15); border-color:rgba(42,157,143,0.3); color:#4ecdc4; }
        .badge-red { background:rgba(230,57,70,0.12); border-color:rgba(230,57,70,0.3); color:#ff6b74; }

        .btn-delete { padding:5px 14px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:12px; transition:background 0.2s; }
        .btn-delete:hover { background:rgba(230,57,70,0.1); }

        .empty { text-align:center; padding:40px; color:var(--text-muted); font-size:14px; }

        @media(max-width:900px) { .layout { grid-template-columns:1fr; } }
        @media(max-width:600px) { nav { padding:14px 20px; } td:nth-child(4) { display:none; } }
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
        <a href="/cinema/admin/seances/liste.php" class="nav-link active">🕐 Séances</a>
        <a href="/cinema/admin/reservations/liste.php" class="nav-link">🎟️ Réservations</a>
        <a href="/cinema/admin/stats.php" class="nav-link">📊 Stats</a>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <h1 class="page-title">Gestion des <span>Séances</span></h1>

    <div class="layout">

        <!-- Formulaire ajout -->
        <div class="card">
            <div class="card-title">➕ Ajouter une séance</div>

            <?php if ($error): ?>
                <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET["deleted"])): ?>
                <div class="msg msg-success">Séance supprimée.</div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Film</label>
                    <select name="id_film" required>
                        <option value="">-- Choisir un film --</option>
                        <?php foreach ($films as $f): ?>
                            <option value="<?= $f["id"] ?>"><?= htmlspecialchars($f["titre"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label>Heure</label>
                    <input type="time" name="heure" required>
                </div>

                <div class="form-group">
                    <label>Salle</label>
                    <select name="salle" required>
                        <option value="">-- Choisir --</option>
                        <option value="Salle 1">Salle 1</option>
                        <option value="Salle 2">Salle 2</option>
                        <option value="Salle 3">Salle 3</option>
                        <option value="Salle 4">Salle 4</option>
                        <option value="Salle VIP">Salle VIP</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nombre de places</label>
                    <input type="number" name="places_total" min="1" max="300" value="100" required>
                </div>

                <button type="submit" class="btn-submit">🕐 Ajouter la séance</button>
            </form>
        </div>

        <!-- Liste des séances -->
        <div class="table-wrap">
            <?php if (empty($seances)): ?>
                <div class="empty">Aucune séance. Ajoutez-en une !</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Film</th>
                        <th>Date & Heure</th>
                        <th>Salle</th>
                        <th>Places</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seances as $s): ?>
                    <?php
                        $pct = $s["places_total"] > 0 ? round(($s["places_dispo"] / $s["places_total"]) * 100) : 0;
                        $badgeClass = $s["places_dispo"] == 0 ? "badge-red" : ($pct < 20 ? "badge-red" : ($pct < 50 ? "badge" : "badge-green"));
                        $label = $s["places_dispo"] == 0 ? "Complet" : $s["places_dispo"]." / ".$s["places_total"];
                    ?>
                    <tr>
                        <td><span class="film-name"><?= htmlspecialchars($s["titre"]) ?></span></td>
                        <td>
                            <?= date("d/m/Y", strtotime($s["date"])) ?><br>
                            <small style="color:var(--text-muted)"><?= substr($s["heure"], 0, 5) ?></small>
                        </td>
                        <td><span class="badge"><?= htmlspecialchars($s["salle"]) ?></span></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= $label ?></span></td>
                        <td>
                            <a href="?delete=<?= $s["id"] ?>" class="btn-delete"
                               onclick="return confirm('Supprimer cette séance ?')">🗑️ Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>
</main>

</body>
</html>