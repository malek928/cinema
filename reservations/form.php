<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: /cinema/auth/auth.php");
    exit;
}

$id_seance = (int)($_GET["id"] ?? 0);
if (!$id_seance) {
    header("Location: /cinema/films/liste.php");
    exit;
}

// Récupérer la séance + film
$stmt = $pdo->prepare("
    SELECT s.*, f.titre, f.affiche, f.genre, f.duree, f.annee
    FROM seance s
    JOIN film f ON s.id_film = f.id
    WHERE s.id = ?
");
$stmt->execute([$id_seance]);
$seance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seance) {
    header("Location: /cinema/films/liste.php");
    exit;
}

$error   = "";
$success = false;

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nb_places = (int)$_POST["nb_places"];

    if ($nb_places < 1) {
        $error = "Veuillez choisir au moins 1 place.";
    } elseif ($nb_places > $seance["places_dispo"]) {
        $error = "Seulement " . $seance["places_dispo"] . " place(s) disponible(s).";
    } else {
        // Transaction : insérer réservation + décrémenter places
        try {
            $pdo->beginTransaction();

            // Vérifier à nouveau les places (sécurité)
            $check = $pdo->prepare("SELECT places_dispo FROM seance WHERE id = ? FOR UPDATE");
            $check->execute([$id_seance]);
            $dispo = $check->fetchColumn();

            if ($dispo < $nb_places) {
                throw new Exception("Plus assez de places disponibles.");
            }

            // Insérer la réservation
            $ins = $pdo->prepare("INSERT INTO reservation (id_user, id_seance, nb_places, statut)
                                   VALUES (?, ?, ?, 'confirmé')");
            $ins->execute([$_SESSION["user_id"], $id_seance, $nb_places]);

            // Décrémenter les places
            $upd = $pdo->prepare("UPDATE seance SET places_dispo = places_dispo - ? WHERE id = ?");
            $upd->execute([$nb_places, $id_seance]);

            $pdo->commit();
            $success = true;

            // Recharger la séance pour afficher les nouvelles places
            $stmt->execute([$id_seance]);
            $seance = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$firstName = $_SESSION["nom"] ?? "Invité";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Réserver</title>
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

        nav { position:relative; z-index:10; display:flex; justify-content:space-between; align-items:center; padding:18px 48px; background:var(--dark2); border-bottom:1px solid #2a2a3a; }
        .logo { font-family:'Playfair Display',serif; font-size:26px; font-weight:700; color:var(--gold); letter-spacing:4px; text-transform:uppercase; text-decoration:none; }
        .logo span { color:var(--red); }
        .nav-right { display:flex; align-items:center; gap:20px; }
        .welcome { font-size:14px; color:var(--text-muted); }
        .welcome strong { color:var(--gold); }
        .nav-link { color:var(--text-muted); text-decoration:none; font-size:14px; padding:6px 14px; border-radius:6px; transition:background 0.2s; }
        .nav-link:hover { background:var(--dark3); color:var(--text); }
        .logout-btn { padding:8px 20px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:13px; transition:background 0.2s; }
        .logout-btn:hover { background:rgba(230,57,70,0.1); }

        main { position:relative; z-index:1; max-width:760px; margin:0 auto; padding:48px 24px; }

        .back-link { display:inline-flex; align-items:center; gap:6px; color:var(--text-muted); text-decoration:none; font-size:13px; margin-bottom:24px; transition:color 0.2s; }
        .back-link:hover { color:var(--gold); }

        /* Card film résumé */
        .film-card { display:flex; gap:20px; background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; padding:20px; margin-bottom:24px; align-items:center; }
        .film-card img { width:70px; height:96px; object-fit:cover; border-radius:8px; flex-shrink:0; }
        .film-card-placeholder { width:70px; height:96px; background:var(--dark4); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:28px; flex-shrink:0; }
        .film-card-info h2 { font-family:'Playfair Display',serif; font-size:20px; margin-bottom:8px; }
        .film-card-meta { display:flex; gap:8px; flex-wrap:wrap; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; background:var(--dark4); border:1px solid #2e2e42; color:var(--text-muted); }
        .badge-gold { background:rgba(201,168,76,0.12); border-color:rgba(201,168,76,0.3); color:var(--gold); }

        /* Card séance */
        .seance-info { background:var(--dark3); border:1px solid #2a2a3a; border-radius:14px; padding:20px; margin-bottom:24px; display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
        .seance-detail { text-align:center; }
        .seance-detail .label { font-size:11px; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); margin-bottom:6px; }
        .seance-detail .value { font-family:'Playfair Display',serif; font-size:22px; color:var(--gold); }
        .seance-detail .value-sm { font-size:15px; font-weight:500; color:var(--text); }

        /* Formulaire */
        .form-card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; padding:28px; }
        .form-title { font-family:'Playfair Display',serif; font-size:20px; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid #2a2a3a; }

        .places-selector { display:flex; align-items:center; gap:16px; margin-bottom:20px; }
        .places-selector label { font-size:11px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); min-width:120px; }

        .counter { display:flex; align-items:center; gap:0; border:1px solid #2e2e42; border-radius:8px; overflow:hidden; }
        .counter button { width:40px; height:40px; background:var(--dark4); border:none; color:var(--text); font-size:18px; cursor:pointer; transition:background 0.2s; }
        .counter button:hover { background:var(--dark3); color:var(--gold); }
        .counter input { width:56px; height:40px; background:var(--dark4); border:none; border-left:1px solid #2e2e42; border-right:1px solid #2e2e42; color:var(--text); font-family:'DM Sans',sans-serif; font-size:16px; font-weight:500; text-align:center; outline:none; }

        .places-dispo { font-size:13px; color:var(--text-muted); margin-bottom:20px; }
        .places-dispo strong { color:var(--gold); }

        /* Résumé prix */
        .summary { background:var(--dark3); border-radius:10px; padding:16px; margin-bottom:20px; }
        .summary-row { display:flex; justify-content:space-between; font-size:14px; color:var(--text-muted); margin-bottom:8px; }
        .summary-row:last-child { margin-bottom:0; color:var(--text); font-weight:500; font-size:15px; border-top:1px solid #2a2a3a; padding-top:10px; margin-top:4px; }
        .summary-row span:last-child { color:var(--gold); }

        .btn-submit { width:100%; padding:14px; background:linear-gradient(135deg,var(--gold),var(--gold-light)); color:var(--dark); border:none; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:15px; font-weight:700; letter-spacing:0.5px; cursor:pointer; transition:opacity 0.2s,transform 0.1s; }
        .btn-submit:hover { opacity:0.9; transform:translateY(-1px); }
        .btn-submit:disabled { opacity:0.4; cursor:not-allowed; transform:none; }

        /* Messages */
        .msg { padding:14px 18px; border-radius:10px; font-size:14px; margin-bottom:20px; }
        .msg-error { background:rgba(230,57,70,0.12); border:1px solid rgba(230,57,70,0.3); color:#ff6b74; }
        .msg-success { background:rgba(42,157,143,0.12); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; text-align:center; }
        .msg-success h3 { font-size:18px; font-weight:600; margin-bottom:8px; }
        .msg-success p { font-size:14px; opacity:0.9; }

        .btn-mes-resa { display:inline-block; margin-top:14px; padding:10px 24px; background:var(--gold); color:var(--dark); border-radius:8px; text-decoration:none; font-weight:700; font-size:14px; }
        .btn-mes-resa:hover { opacity:0.9; }

        @media(max-width:600px) {
            nav { padding:14px 20px; }
            .seance-info { grid-template-columns:1fr; }
            .film-card { flex-direction:column; }
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
        <a href="/cinema/reservations/liste.php" class="nav-link">🎟️ Mes réservations</a>
        <span class="welcome">Bonjour, <strong><?= htmlspecialchars($firstName) ?></strong></span>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <a href="/cinema/seances/liste.php?id=<?= $seance["id_film"] ?>" class="back-link">← Retour aux séances</a>

    <!-- Résumé film -->
    <div class="film-card">
        <?php if (!empty($seance["affiche"])): ?>
            <img src="/cinema/<?= htmlspecialchars($seance["affiche"]) ?>" alt="">
        <?php else: ?>
            <div class="film-card-placeholder">🎬</div>
        <?php endif; ?>
        <div class="film-card-info">
            <h2><?= htmlspecialchars($seance["titre"]) ?></h2>
            <div class="film-card-meta">
                <span class="badge badge-gold"><?= htmlspecialchars($seance["genre"] ?? "") ?></span>
                <span class="badge"><?= $seance["annee"] ?></span>
                <span class="badge">⏱ <?= $seance["duree"] ?> min</span>
            </div>
        </div>
    </div>

    <!-- Infos séance -->
    <div class="seance-info">
        <div class="seance-detail">
            <div class="label">Date</div>
            <div class="value-sm"><?= date("d/m/Y", strtotime($seance["date"])) ?></div>
        </div>
        <div class="seance-detail">
            <div class="label">Heure</div>
            <div class="value"><?= substr($seance["heure"], 0, 5) ?></div>
        </div>
        <div class="seance-detail">
            <div class="label">Salle</div>
            <div class="value-sm"><?= htmlspecialchars($seance["salle"]) ?></div>
        </div>
    </div>

    <!-- Succès -->
    <?php if ($success): ?>
        <div class="msg msg-success">
            <h3>🎉 Réservation confirmée !</h3>
            <p>Votre réservation pour <strong><?= htmlspecialchars($seance["titre"]) ?></strong> a été enregistrée.</p>
            <a href="/cinema/reservations/liste.php" class="btn-mes-resa">Voir mes réservations</a>
        </div>
    <?php endif; ?>

    <!-- Formulaire -->
    <?php if (!$success): ?>
    <div class="form-card">
        <h3 class="form-title">🎟️ Choisir vos places</h3>

        <?php if ($error): ?>
            <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="places-dispo">
            Places disponibles : <strong><?= $seance["places_dispo"] ?></strong> / <?= $seance["places_total"] ?>
        </div>

        <form method="POST" id="resa-form">
            <div class="places-selector">
                <label>Nombre de places</label>
                <div class="counter">
                    <button type="button" onclick="changeQty(-1)">−</button>
                    <input type="number" name="nb_places" id="nb_places" value="1"
                           min="1" max="<?= $seance["places_dispo"] ?>" readonly>
                    <button type="button" onclick="changeQty(1)">+</button>
                </div>
            </div>

            <div class="summary">
                <div class="summary-row">
                    <span>Film</span>
                    <span><?= htmlspecialchars($seance["titre"]) ?></span>
                </div>
                <div class="summary-row">
                    <span>Séance</span>
                    <span><?= date("d/m/Y", strtotime($seance["date"])) ?> à <?= substr($seance["heure"],0,5) ?></span>
                </div>
                <div class="summary-row">
                    <span>Salle</span>
                    <span><?= htmlspecialchars($seance["salle"]) ?></span>
                </div>
                <div class="summary-row">
                    <span>Nombre de places</span>
                    <span id="summary-places">1</span>
                </div>
            </div>

            <button type="submit" class="btn-submit"
                    <?= $seance["places_dispo"] == 0 ? "disabled" : "" ?>>
                <?= $seance["places_dispo"] == 0 ? "Séance complète" : "✅ Confirmer la réservation" ?>
            </button>
        </form>
    </div>
    <?php endif; ?>
</main>

<script>
const max = <?= $seance["places_dispo"] ?>;

function changeQty(delta) {
    const input = document.getElementById('nb_places');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > max) val = max;
    input.value = val;
    document.getElementById('summary-places').textContent = val;
}
</script>

</body>
</html>