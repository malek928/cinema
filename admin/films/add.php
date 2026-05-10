<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

// Protection admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: /cinema/auth/auth.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre    = trim($_POST["titre"]);
    $synopsis = trim($_POST["synopsis"]);
    $duree    = (int)$_POST["duree"];
    $genre    = trim($_POST["genre"]);
    $annee    = (int)$_POST["annee"];
    $affiche  = "";

    // Upload affiche
    if (isset($_FILES["affiche"]) && $_FILES["affiche"]["error"] === 0) {
        $ext      = pathinfo($_FILES["affiche"]["name"], PATHINFO_EXTENSION);
        $allowed  = ["jpg", "jpeg", "png", "webp"];

        if (!in_array(strtolower($ext), $allowed)) {
            $error = "Format d'image non autorisé. Utilisez jpg, png ou webp.";
        } else {
            $filename = uniqid("film_") . "." . $ext;
            $dest     = __DIR__ . "/../../uploads/affiches/" . $filename;

            if (!is_dir(__DIR__ . "/../../uploads/affiches/")) {
                mkdir(__DIR__ . "/../../uploads/affiches/", 0755, true);
            }

            if (move_uploaded_file($_FILES["affiche"]["tmp_name"], $dest)) {
                $affiche = "uploads/affiches/" . $filename;
            } else {
                $error = "Erreur lors de l'upload de l'affiche.";
            }
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO film (titre, synopsis, affiche, duree, genre, annee)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titre, $synopsis, $affiche, $duree, $genre, $annee]);
        $success = "Film ajouté avec succès !";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Ajouter un film</title>
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

        /* Filmstrip */
        .filmstrip { width:100%; height:36px; background:var(--dark2); border-bottom:2px solid var(--gold); display:flex; align-items:center; overflow:hidden; position:relative; z-index:10; }
        .filmstrip-holes { display:flex; gap:18px; padding:0 12px; animation:scroll-strip 12s linear infinite; }
        .hole { width:18px; height:20px; background:var(--dark); border-radius:3px; flex-shrink:0; border:1px solid #2a2a3a; }
        @keyframes scroll-strip { from{transform:translateX(0)} to{transform:translateX(-300px)} }

        /* Nav */
        nav { position:relative; z-index:10; display:flex; justify-content:space-between; align-items:center; padding:18px 48px; background:var(--dark2); border-bottom:1px solid #2a2a3a; }
        .logo { font-family:'Playfair Display',serif; font-size:26px; font-weight:700; color:var(--gold); letter-spacing:4px; text-transform:uppercase; text-decoration:none; }
        .logo span { color:var(--red); }
        .nav-links { display:flex; gap:16px; align-items:center; }
        .nav-link { color:var(--text-muted); text-decoration:none; font-size:14px; padding:6px 14px; border-radius:6px; transition:background 0.2s,color 0.2s; }
        .nav-link:hover { background:var(--dark3); color:var(--text); }
        .nav-link.active { color:var(--gold); border:1px solid rgba(201,168,76,0.3); }
        .logout-btn { padding:8px 20px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:13px; transition:background 0.2s; }
        .logout-btn:hover { background:rgba(230,57,70,0.1); }

        /* Main */
        main { position:relative; z-index:1; max-width:760px; margin:0 auto; padding:48px 24px; }
        .page-header { margin-bottom:32px; }
        .page-title { font-family:'Playfair Display',serif; font-size:30px; color:var(--text); margin-bottom:6px; }
        .page-title span { color:var(--gold); }
        .page-sub { font-size:14px; color:var(--text-muted); }

        /* Card */
        .card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:16px; padding:36px; }

        /* Form */
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-full { grid-column:1/-1; }
        .form-group { display:flex; flex-direction:column; gap:6px; }
        .form-group label { font-size:11px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); }
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding:11px 14px; background:var(--dark4); border:1px solid #2e2e42;
            border-radius:8px; color:var(--text); font-family:'DM Sans',sans-serif;
            font-size:14px; transition:border-color 0.2s,box-shadow 0.2s; outline:none;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,168,76,0.12); }
        .form-group textarea { resize:vertical; min-height:100px; }
        .form-group select option { background:var(--dark4); }

        /* Upload zone */
        .upload-zone {
            border:2px dashed #2e2e42; border-radius:10px; padding:28px;
            text-align:center; cursor:pointer; transition:border-color 0.2s,background 0.2s;
            position:relative;
        }
        .upload-zone:hover { border-color:var(--gold); background:rgba(201,168,76,0.04); }
        .upload-zone input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .upload-icon { font-size:32px; margin-bottom:8px; }
        .upload-text { font-size:14px; color:var(--text-muted); }
        .upload-text strong { color:var(--gold); }
        #preview-img { display:none; width:100%; max-height:200px; object-fit:contain; border-radius:8px; margin-top:12px; }

        /* Bouton submit */
        .btn-submit {
            width:100%; padding:14px; background:linear-gradient(135deg,var(--gold),var(--gold-light));
            color:var(--dark); border:none; border-radius:8px; font-family:'DM Sans',sans-serif;
            font-size:14px; font-weight:700; letter-spacing:1px; text-transform:uppercase;
            cursor:pointer; margin-top:8px; transition:opacity 0.2s,transform 0.1s;
        }
        .btn-submit:hover { opacity:0.9; transform:translateY(-1px); }

        /* Liens action */
        .btn-secondary {
            display:inline-block; padding:10px 22px; border:1px solid #2e2e42;
            color:var(--text-muted); text-decoration:none; border-radius:8px;
            font-size:13px; transition:background 0.2s,color 0.2s;
        }
        .btn-secondary:hover { background:var(--dark3); color:var(--text); }

        /* Messages */
        .msg { padding:12px 16px; border-radius:8px; font-size:14px; margin-bottom:20px; }
        .msg-error { background:rgba(230,57,70,0.12); border:1px solid rgba(230,57,70,0.3); color:#ff6b74; }
        .msg-success { background:rgba(42,157,143,0.12); border:1px solid rgba(42,157,143,0.3); color:#4ecdc4; }

        .actions { display:flex; gap:12px; margin-top:20px; }
        @media(max-width:600px) { .form-grid { grid-template-columns:1fr; } nav { padding:14px 20px; } }
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
    <div class="page-header">
        <h1 class="page-title">Ajouter un <span>Film</span></h1>
        <p class="page-sub">Remplissez les informations du nouveau film</p>
    </div>

    <?php if ($error): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg msg-success">
            <?= htmlspecialchars($success) ?>
            — <a href="/cinema/admin/films/liste.php" style="color:inherit;font-weight:600;">Voir tous les films</a>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="form-group form-full">
                    <label>Titre du film</label>
                    <input type="text" name="titre" placeholder="Ex: Inception" required>
                </div>

                <div class="form-group form-full">
                    <label>Synopsis</label>
                    <textarea name="synopsis" placeholder="Résumé du film..."></textarea>
                </div>

                <div class="form-group">
                    <label>Genre</label>
                    <select name="genre">
                        <option value="">-- Choisir --</option>
                        <option>Action</option>
                        <option>Comédie</option>
                        <option>Drame</option>
                        <option>Horreur</option>
                        <option>Science-Fiction</option>
                        <option>Thriller</option>
                        <option>Animation</option>
                        <option>Romance</option>
                        <option>Aventure</option>
                        <option>Documentaire</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Année de sortie</label>
                    <input type="number" name="annee" min="1900" max="2030" placeholder="2024">
                </div>

                <div class="form-group">
                    <label>Durée (minutes)</label>
                    <input type="number" name="duree" min="1" placeholder="120">
                </div>

                <div class="form-group">
                    <!-- colonne vide pour symétrie -->
                </div>

                <div class="form-group form-full">
                    <label>Affiche du film</label>
                    <div class="upload-zone" id="upload-zone">
                        <input type="file" name="affiche" accept="image/*" id="affiche-input"
                               onchange="previewImage(this)">
                        <div class="upload-icon">🖼️</div>
                        <div class="upload-text">
                            <strong>Cliquez ou glissez</strong> une image ici<br>
                            JPG, PNG, WEBP acceptés
                        </div>
                        <img id="preview-img" src="" alt="Aperçu">
                    </div>
                </div>

                <div class="form-full">
                    <button type="submit" class="btn-submit">🎬 Ajouter le film</button>
                </div>

            </div>
        </form>
    </div>

    <div class="actions">
        <a href="/cinema/admin/films/liste.php" class="btn-secondary">← Retour à la liste</a>
    </div>
</main>

<script>
function previewImage(input) {
    const img = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>