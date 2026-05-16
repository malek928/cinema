<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: /cinema/auth/auth.php");
    exit;
}

$id_seance  = (int)($_GET["id"] ?? $_POST["id_seance"] ?? 0);
$nb_places  = (int)($_GET["nb"] ?? $_POST["nb_places"] ?? 1);

if (!$id_seance || !$nb_places) {
    header("Location: /cinema/films/liste.php");
    exit;
}

// Récupérer la séance + film
$stmt = $pdo->prepare("
    SELECT s.*, f.titre, f.affiche, f.genre, f.duree
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

$prix_place  = 12; // prix fictif
$total       = $nb_places * $prix_place;
$firstName   = $_SESSION["nom"] ?? "Invité";

$error   = "";
$success = false;

// Traitement paiement (simulé)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["pay"])) {
    $nb_places = (int)$_POST["nb_places"];

    // Vérifier disponibilité
    if ($nb_places > $seance["places_dispo"]) {
        $error = "Plus assez de places disponibles.";
    } else {
        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare("SELECT places_dispo FROM seance WHERE id = ? FOR UPDATE");
            $check->execute([$id_seance]);
            $dispo = $check->fetchColumn();

            if ($dispo < $nb_places) {
                throw new Exception("Plus assez de places.");
            }

            $ins = $pdo->prepare("INSERT INTO reservation (id_user, id_seance, nb_places, statut)
                                   VALUES (?, ?, ?, 'confirmé')");
            $ins->execute([$_SESSION["user_id"], $id_seance, $nb_places]);

            $upd = $pdo->prepare("UPDATE seance SET places_dispo = places_dispo - ? WHERE id = ?");
            $upd->execute([$nb_places, $id_seance]);

            $pdo->commit();
            $success = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Paiement</title>
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
        .nav-right { display:flex; align-items:center; gap:12px; }
        .welcome { font-size:14px; color:var(--text-muted); }
        .welcome strong { color:var(--gold); }
        .logout-btn { padding:8px 20px; background:transparent; border:1px solid var(--red); color:var(--red); border-radius:6px; text-decoration:none; font-size:13px; transition:background 0.2s; }
        .logout-btn:hover { background:rgba(230,57,70,0.1); }

        main { position:relative; z-index:1; max-width:960px; margin:0 auto; padding:40px 24px; }

        .back-link { display:inline-flex; align-items:center; gap:6px; color:var(--text-muted); text-decoration:none; font-size:13px; margin-bottom:24px; transition:color 0.2s; }
        .back-link:hover { color:var(--gold); }

        /* Layout */
        .layout { display:grid; grid-template-columns:1fr 380px; gap:24px; align-items:start; }

        /* Carte de paiement visuelle */
        .card-visual {
            width:100%; max-width:340px; height:200px;
            background:linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            border-radius:16px; padding:24px;
            position:relative; overflow:hidden;
            border:1px solid rgba(201,168,76,0.2);
            box-shadow:0 20px 60px rgba(0,0,0,0.5);
            margin-bottom:24px;
        }
        .card-visual::before {
            content:''; position:absolute;
            width:200px; height:200px;
            background:rgba(201,168,76,0.05);
            border-radius:50%;
            top:-60px; right:-40px;
        }
        .card-chip {
            width:44px; height:34px;
            background:linear-gradient(135deg, var(--gold), var(--gold-light));
            border-radius:6px; margin-bottom:20px;
            display:flex; align-items:center; justify-content:center;
        }
        .card-chip-lines { width:28px; }
        .card-chip-lines div { height:2px; background:rgba(0,0,0,0.3); margin:4px 0; border-radius:1px; }
        .card-number-display {
            font-size:18px; letter-spacing:3px; color:var(--text);
            font-family:'Playfair Display',serif; margin-bottom:16px;
        }
        .card-footer { display:flex; justify-content:space-between; align-items:flex-end; }
        .card-label { font-size:9px; letter-spacing:2px; text-transform:uppercase; color:rgba(240,237,230,0.5); margin-bottom:4px; }
        .card-value { font-size:13px; color:var(--text); font-weight:500; }
        .card-logo { font-family:'Playfair Display',serif; font-size:18px; color:var(--gold); }

        /* Formulaire */
        .form-card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; padding:28px; }
        .form-title { font-family:'Playfair Display',serif; font-size:20px; margin-bottom:6px; }
        .form-title span { color:var(--gold); }
        .form-sub { font-size:13px; color:var(--text-muted); margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid #2a2a3a; }

        .form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
        .form-group label { font-size:11px; font-weight:500; letter-spacing:1.5px; text-transform:uppercase; color:var(--text-muted); }
        .form-group input {
            padding:12px 14px; background:var(--dark4);
            border:1px solid #2e2e42; border-radius:8px;
            color:var(--text); font-family:'DM Sans',sans-serif;
            font-size:15px; transition:border-color 0.2s; outline:none;
            letter-spacing:1px;
        }
        .form-group input:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,168,76,0.12); }
        .form-group input.error { border-color:var(--red); }
        .form-group input.valid { border-color:#2a9d8f; }
        .field-error { font-size:11px; color:#ff6b74; margin-top:4px; display:none; }

        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

        .secure-badge { display:flex; align-items:center; gap:8px; font-size:12px; color:var(--text-muted); margin-bottom:16px; padding:10px 14px; background:rgba(42,157,143,0.08); border:1px solid rgba(42,157,143,0.2); border-radius:8px; }

        .btn-pay {
            width:100%; padding:14px;
            background:linear-gradient(135deg,var(--gold),var(--gold-light));
            color:var(--dark); border:none; border-radius:8px;
            font-family:'DM Sans',sans-serif; font-size:15px;
            font-weight:700; letter-spacing:0.5px;
            cursor:pointer; transition:opacity 0.2s;
            position:relative; overflow:hidden;
        }
        .btn-pay:hover { opacity:0.9; }
        .btn-pay:disabled { opacity:0.6; cursor:not-allowed; }

        /* Résumé commande */
        .summary-card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; padding:24px; position:sticky; top:20px; }
        .summary-title { font-family:'Playfair Display',serif; font-size:18px; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid #2a2a3a; }
        .summary-title span { color:var(--gold); }

        .summary-film { display:flex; gap:14px; margin-bottom:20px; }
        .summary-film img { width:54px; height:74px; object-fit:cover; border-radius:8px; flex-shrink:0; }
        .summary-film-ph { width:54px; height:74px; background:var(--dark4); border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .summary-film-info h3 { font-family:'Playfair Display',serif; font-size:16px; margin-bottom:6px; }
        .summary-film-meta { font-size:12px; color:var(--text-muted); }

        .summary-line { display:flex; justify-content:space-between; font-size:13px; color:var(--text-muted); margin-bottom:10px; }
        .summary-line.total { font-size:16px; font-weight:600; color:var(--text); border-top:1px solid #2a2a3a; padding-top:12px; margin-top:4px; }
        .summary-line.total span:last-child { color:var(--gold); font-family:'Playfair Display',serif; font-size:20px; }

        /* Processing overlay */
        .processing-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.85); z-index:1000;
            align-items:center; justify-content:center;
            flex-direction:column; gap:20px;
        }
        .processing-overlay.show { display:flex; }
        .spinner {
            width:56px; height:56px;
            border:3px solid #2a2a3a;
            border-top-color:var(--gold);
            border-radius:50%;
            animation:spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform:rotate(360deg); } }
        .processing-text { font-family:'Playfair Display',serif; font-size:20px; color:var(--text); }
        .processing-sub { font-size:13px; color:var(--text-muted); }

        /* Success */
        .success-card {
            background:var(--dark2); border:1px solid rgba(42,157,143,0.3);
            border-radius:16px; padding:48px; text-align:center;
            max-width:500px; margin:0 auto;
        }
        .success-icon { font-size:64px; margin-bottom:16px; animation:pop 0.5s ease; }
        @keyframes pop { 0%{transform:scale(0)} 70%{transform:scale(1.2)} 100%{transform:scale(1)} }
        .success-title { font-family:'Playfair Display',serif; font-size:28px; margin-bottom:8px; color:var(--text); }
        .success-sub { font-size:14px; color:var(--text-muted); margin-bottom:24px; line-height:1.6; }
        .success-details { background:var(--dark3); border-radius:10px; padding:16px; margin-bottom:24px; text-align:left; }
        .success-detail-line { display:flex; justify-content:space-between; font-size:13px; padding:6px 0; border-bottom:1px solid #2a2a3a; }
        .success-detail-line:last-child { border-bottom:none; }
        .success-detail-line span:last-child { color:var(--gold); font-weight:500; }
        .btn-resa { display:inline-block; padding:12px 28px; background:linear-gradient(135deg,var(--gold),var(--gold-light)); color:var(--dark); border-radius:8px; text-decoration:none; font-weight:700; font-size:14px; }

        .msg-error { padding:12px 16px; border-radius:8px; font-size:14px; margin-bottom:20px; background:rgba(230,57,70,0.12); border:1px solid rgba(230,57,70,0.3); color:#ff6b74; }

        @media(max-width:760px) {
            .layout { grid-template-columns:1fr; }
            nav { padding:14px 20px; }
            .form-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<!-- Processing overlay -->
<div class="processing-overlay" id="processingOverlay">
    <div class="spinner"></div>
    <div class="processing-text">Traitement en cours...</div>
    <div class="processing-sub">Veuillez ne pas fermer cette page</div>
</div>

<div class="filmstrip">
    <div class="filmstrip-holes">
        <?php for($i=0;$i<30;$i++): ?><div class="hole"></div><?php endfor; ?>
    </div>
</div>

<nav>
    <a href="/cinema/films/liste.php" class="logo">Ciné<span>Max</span></a>
    <div class="nav-right">
        <span class="welcome">Bonjour, <strong><?= htmlspecialchars($firstName) ?></strong></span>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <?php if ($success): ?>
        <!-- Page succès -->
        <div class="success-card">
            <div class="success-icon">🎉</div>
            <h2 class="success-title">Paiement confirmé !</h2>
            <p class="success-sub">Votre réservation pour <strong><?= htmlspecialchars($seance["titre"]) ?></strong> a été enregistrée avec succès.</p>
            <div class="success-details">
                <div class="success-detail-line">
                    <span>Film</span>
                    <span><?= htmlspecialchars($seance["titre"]) ?></span>
                </div>
                <div class="success-detail-line">
                    <span>Date</span>
                    <span><?= date("d/m/Y", strtotime($seance["date"])) ?> à <?= substr($seance["heure"],0,5) ?></span>
                </div>
                <div class="success-detail-line">
                    <span>Salle</span>
                    <span><?= htmlspecialchars($seance["salle"]) ?></span>
                </div>
                <div class="success-detail-line">
                    <span>Places</span>
                    <span><?= $nb_places ?> place(s)</span>
                </div>
                <div class="success-detail-line">
                    <span>Montant payé</span>
                    <span><?= $nb_places * $prix_place ?> €</span>
                </div>
            </div>
            <a href="/cinema/reservations/liste.php" class="btn-resa">🎟️ Voir mes réservations</a>
        </div>

    <?php else: ?>

        <a href="/cinema/reservations/form.php?id=<?= $id_seance ?>" class="back-link">← Retour</a>

        <?php if ($error): ?>
            <div class="msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="layout">

            <!-- Formulaire paiement -->
            <div>
                <!-- Carte visuelle -->
                <div class="card-visual">
                    <div class="card-chip">
                        <div class="card-chip-lines">
                            <div></div><div></div><div></div>
                        </div>
                    </div>
                    <div class="card-number-display" id="cardNumberDisplay">
                        •••• •••• •••• ••••
                    </div>
                    <div class="card-footer">
                        <div>
                            <div class="card-label">Titulaire</div>
                            <div class="card-value" id="cardNameDisplay">VOTRE NOM</div>
                        </div>
                        <div>
                            <div class="card-label">Expire</div>
                            <div class="card-value" id="cardExpiryDisplay">MM/AA</div>
                        </div>
                        <div class="card-logo">VISA</div>
                    </div>
                </div>

                <!-- Formulaire -->
                <div class="form-card">
                    <h2 class="form-title">Paiement <span>sécurisé</span></h2>
                    <p class="form-sub">Vos données sont protégées et chiffrées</p>

                    <div class="secure-badge">
                        🔒 Connexion sécurisée SSL — Données fictives pour démonstration
                    </div>

                    <form method="POST" id="paymentForm" onsubmit="return processPayment(event)">
                        <input type="hidden" name="pay" value="1">
                        <input type="hidden" name="id_seance" value="<?= $id_seance ?>">
                        <input type="hidden" name="nb_places" value="<?= $nb_places ?>">

                        <div class="form-group">
                            <label>Numéro de carte</label>
                            <input type="text" id="cardNumber" name="card_number"
                                   placeholder="1234 5678 9012 3456"
                                   maxlength="19" autocomplete="off"
                                   oninput="formatCardNumber(this)">
                            <span class="field-error" id="errCard">Numéro de carte invalide</span>
                        </div>

                        <div class="form-group">
                            <label>Nom sur la carte</label>
                            <input type="text" id="cardName" name="card_name"
                                   placeholder="JEAN DUPONT"
                                   oninput="updateCardName(this)">
                            <span class="field-error" id="errName">Nom requis</span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Date d'expiration</label>
                                <input type="text" id="cardExpiry" name="card_expiry"
                                       placeholder="MM/AA" maxlength="5"
                                       oninput="formatExpiry(this)">
                                <span class="field-error" id="errExpiry">Date invalide</span>
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" id="cardCvv" name="card_cvv"
                                       placeholder="•••" maxlength="3"
                                       oninput="this.value=this.value.replace(/\D/g,'')">
                                <span class="field-error" id="errCvv">CVV invalide</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-pay" id="payBtn">
                            💳 Payer <?= $total ?> €
                        </button>
                    </form>
                </div>
            </div>

            <!-- Résumé -->
            <div class="summary-card">
                <h3 class="summary-title">Résumé de la <span>commande</span></h3>

                <div class="summary-film">
                    <?php if (!empty($seance["affiche"])): ?>
                        <img src="/cinema/<?= htmlspecialchars($seance["affiche"]) ?>" alt="">
                    <?php else: ?>
                        <div class="summary-film-ph">🎬</div>
                    <?php endif; ?>
                    <div class="summary-film-info">
                        <h3><?= htmlspecialchars($seance["titre"]) ?></h3>
                        <div class="summary-film-meta">
                            <?= date("d/m/Y", strtotime($seance["date"])) ?> — <?= substr($seance["heure"],0,5) ?><br>
                            <?= htmlspecialchars($seance["salle"]) ?>
                        </div>
                    </div>
                </div>

                <div class="summary-line">
                    <span>Prix par place</span>
                    <span><?= $prix_place ?> €</span>
                </div>
                <div class="summary-line">
                    <span>Nombre de places</span>
                    <span><?= $nb_places ?></span>
                </div>
                <div class="summary-line">
                    <span>Frais de service</span>
                    <span>0 €</span>
                </div>
                <div class="summary-line total">
                    <span>Total</span>
                    <span><?= $total ?> €</span>
                </div>
            </div>

        </div>
    <?php endif; ?>
</main>

<script>
// Mise à jour de la carte visuelle
function formatCardNumber(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 16);
    let formatted = v.replace(/(.{4})/g, '$1 ').trim();
    input.value = formatted;

    let display = v.padEnd(16, '•');
    document.getElementById('cardNumberDisplay').textContent =
        display.substring(0,4) + ' ' + display.substring(4,8) + ' ' +
        display.substring(8,12) + ' ' + display.substring(12,16);
}

function updateCardName(input) {
    let val = input.value.toUpperCase() || 'VOTRE NOM';
    document.getElementById('cardNameDisplay').textContent = val;
}

function formatExpiry(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 4);
    if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2);
    input.value = v;
    document.getElementById('cardExpiryDisplay').textContent = v || 'MM/AA';
}

// Validation + soumission
function processPayment(e) {
    e.preventDefault();
    let valid = true;

    // Valider numéro carte
    const cardNum = document.getElementById('cardNumber').value.replace(/\s/g,'');
    if (cardNum.length !== 16) {
        showError('cardNumber', 'errCard'); valid = false;
    } else clearError('cardNumber', 'errCard');

    // Valider nom
    const cardName = document.getElementById('cardName').value.trim();
    if (cardName.length < 2) {
        showError('cardName', 'errName'); valid = false;
    } else clearError('cardName', 'errName');

    // Valider expiry
    const expiry = document.getElementById('cardExpiry').value;
    if (!/^\d{2}\/\d{2}$/.test(expiry)) {
        showError('cardExpiry', 'errExpiry'); valid = false;
    } else clearError('cardExpiry', 'errExpiry');

    // Valider CVV
    const cvv = document.getElementById('cardCvv').value;
    if (cvv.length !== 3) {
        showError('cardCvv', 'errCvv'); valid = false;
    } else clearError('cardCvv', 'errCvv');

    if (!valid) return false;

    // Simuler traitement
    document.getElementById('processingOverlay').classList.add('show');
    document.getElementById('payBtn').disabled = true;

    setTimeout(() => {
        document.getElementById('paymentForm').submit();
    }, 2500);

    return false;
}

function showError(inputId, errId) {
    document.getElementById(inputId).classList.add('error');
    document.getElementById(errId).style.display = 'block';
}

function clearError(inputId, errId) {
    document.getElementById(inputId).classList.remove('error');
    document.getElementById(inputId).classList.add('valid');
    document.getElementById(errId).style.display = 'none';
}
</script>

</body>
</html>