<?php
session_start();
require_once __DIR__ . "/config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: /cinema/auth/auth.php");
    exit;
}

$success = "";
$error   = "";

// Récupérer les infos actuelles
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Modifier les infos
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $nom       = trim($_POST["nom"]);
    $prenom    = trim($_POST["prenom"]);
    $telephone = trim($_POST["telephone"]);

    if (empty($nom) || empty($prenom)) {
        $error = "Le nom et le prénom sont obligatoires.";
    } else {
        $pdo->prepare("UPDATE user SET nom=?, prenom=?, telephone=? WHERE id=?")
            ->execute([$nom, $prenom, $telephone, $_SESSION["user_id"]]);
        $_SESSION["nom"] = $nom;
        $success = "Profil mis à jour avec succès !";
        // Recharger les données
        $stmt->execute([$_SESSION["user_id"]]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Changer le mot de passe
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_password"])) {
    $old_password = $_POST["old_password"];
    $new_password = $_POST["new_password"];
    $confirm      = $_POST["confirm_password"];

    if (!password_verify($old_password, $user["password"])) {
        $error = "Mot de passe actuel incorrect.";
    } elseif (strlen($new_password) < 6) {
        $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } elseif ($new_password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE user SET password=? WHERE id=?")
            ->execute([$hashed, $_SESSION["user_id"]]);
        $success = "Mot de passe modifié avec succès !";
    }
}

// Compter les réservations
$nb_resa = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE id_user = ? AND statut != 'annulé'");
$nb_resa->execute([$_SESSION["user_id"]]);
$nb_resa = $nb_resa->fetchColumn();

$nb_annule = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE id_user = ? AND statut = 'annulé'");
$nb_annule->execute([$_SESSION["user_id"]]);
$nb_annule = $nb_annule->fetchColumn();

$firstName = $user["nom"] ?? "Invité";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Mon Profil</title>
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
        .nav-right { display:flex; align-items:center; gap:16px; }
        .nav-link { color:var(--text-muted); text-decoration:none; font-size:14px; padding:6px 14px; border-radius:6px; transition:background 0.2s; }
        .nav-link:hover { background:var(--dark3); color:var(--text); }
        .nav-link.active { color:var(--gold); border:1px solid rgba(201,168,76,0.3); }
        .logout-btn { padding:8px 20px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:13px; transition:background 0.2s; }
        .logout-btn:hover { background:rgba(230,57,70,0.1); }

        main { position:relative; z-index:1; max-width:900px; margin:0 auto; padding:48px 24px; }

        .page-title { font-family:'Playfair Display',serif; font-size:30px; margin-bottom:28px; }
        .page-title span { color:var(--gold); }

        /* Avatar + stats */
        .profile-hero { display:flex; gap:28px; align-items:center; background:var(--dark2); border:1px solid #2a2a3a; border-radius:16px; padding:28px; margin-bottom:24px; }
        .avatar { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,var(--gold),var(--gold-light)); display:flex; align-items:center; justify-content:center; font-family:'Playfair Display',serif; font-size:32px; color:var(--dark); font-weight:700; flex-shrink:0; }
        .profile-info h2 { font-family:'Playfair Display',serif; font-size:24px; margin-bottom:4px; }
        .profile-info p { font-size:14px; color:var(--text-muted); margin-bottom:12px; }
        .profile-stats { display:flex; gap:20px; }
        .pstat { text-align:center; }
        .pstat-num { font-family:'Playfair Display',serif; font-size:22px; color:var(--gold); }
        .pstat-label { font-size:11px; color:var(--text-muted); }

        /* Grid 2 colonnes */
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }

        /* Cards formulaire */
        .card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; padding:28px; }
        .card-title { font-family:'Playfair Display',serif; font-size:18px; margin-bottom:6px; }
        .card-title span { color:var(--gold); }
        .card-sub { font-size:13px; color:var(--text-muted); margin-bottom:22px; padding-bottom:16px; border-bottom:1px solid #2a2a3a; }

        .form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
        .form-group label { font-size:11px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); }
        .form-group input { padding:10px 14px; background:var(--dark4); border:1px solid #2e2e42; border-radius:8px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:14px; transition:border-color 0.2s; outline:none; }
        .form-group input:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,168,76,0.12); }
        .form-group input:disabled { opacity:0.5; cursor:not-allowed; }

        .btn-submit { width:100%; padding:12px; background:linear-gradient(135deg,var(--gold),var(--gold-light)); color:var(--dark); border:none; border-radius:8px; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:700; letter-spacing:0.5px; cursor:pointer; transition:opacity 0.2s; margin-top:4px; }
        .btn-submit:hover { opacity:0.9; }

        /* Messages */
        .msg { padding:12px 16px; border-radius:8px; font-size:13px; margin-bottom:16px; }
        .msg-error { background:rgba(230,57,70,0.12); border:1px solid rgba(230,57,70,0.3); color:#ff6b74; }
        .msg-success { background:rgba(42,157,143,0.12); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; }

        /* Lien mes réservations */
        .btn-resa { display:block; text-align:center; padding:12px; border:1px solid var(--gold); color:var(--gold); border-radius:8px; text-decoration:none; font-size:14px; font-weight:500; margin-top:16px; transition:background 0.2s; }
        .btn-resa:hover { background:rgba(201,168,76,0.08); }

        @media(max-width:700px) {
            .grid2 { grid-template-columns:1fr; }
            .profile-hero { flex-direction:column; text-align:center; }
            .profile-stats { justify-content:center; }
            nav { padding:14px 20px; }
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
        <a href="/cinema/films/liste.php" class="nav-link">🎬 Films</a>
        <a href="/cinema/reservations/liste.php" class="nav-link">🎟️ Mes réservations</a>
        <a href="/cinema/profil.php" class="nav-link active">👤 Profil</a>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <h1 class="page-title">Mon <span>Profil</span></h1>

    <?php if ($error): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Hero profil -->
    <div class="profile-hero">
        <div class="avatar"><?= strtoupper(substr($user["prenom"], 0, 1)) ?></div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($user["prenom"]." ".$user["nom"]) ?></h2>
            <p><?= htmlspecialchars($user["email"]) ?></p>
            <div class="profile-stats">
                <div class="pstat">
                    <div class="pstat-num"><?= $nb_resa ?></div>
                    <div class="pstat-label">Réservations</div>
                </div>
                <div class="pstat">
                    <div class="pstat-num"><?= $nb_annule ?></div>
                    <div class="pstat-label">Annulées</div>
                </div>
                <div class="pstat">
                    <div class="pstat-num"><?= date("Y", strtotime($user["id"] ? "now" : "now")) ?></div>
                    <div class="pstat-label">Membre depuis</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid2">

        <!-- Modifier infos -->
        <div class="card">
            <div class="card-title">Informations <span>personnelles</span></div>
            <div class="card-sub">Modifier vos données de profil</div>

            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($user["nom"]) ?>" required>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($user["prenom"]) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($user["email"]) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" value="<?= htmlspecialchars($user["telephone"] ?? "") ?>" placeholder="+216 XX XXX XXX">
                </div>
                <button type="submit" class="btn-submit">💾 Enregistrer</button>
            </form>

            <a href="/cinema/reservations/liste.php" class="btn-resa">🎟️ Voir mes réservations</a>
        </div>

        <!-- Changer mot de passe -->
        <div class="card">
            <div class="card-title">Changer le <span>mot de passe</span></div>
            <div class="card-sub">Sécurisez votre compte</div>

            <form method="POST">
                <input type="hidden" name="update_password" value="1">
                <div class="form-group">
                    <label>Mot de passe actuel</label>
                    <input type="password" name="old_password" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="new_password" placeholder="••••••••" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">🔒 Changer le mot de passe</button>
            </form>
        </div>

    </div>
</main>

</body>
</html>