<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax — Connexion</title>
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
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--dark);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Background cinéma */
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

        /* Film strip top */
        .filmstrip {
            width: 100%;
            height: 36px;
            background: var(--dark2);
            border-bottom: 2px solid var(--gold);
            display: flex;
            align-items: center;
            gap: 0;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .filmstrip-holes {
            display: flex;
            gap: 18px;
            padding: 0 12px;
            animation: scroll-strip 12s linear infinite;
        }

        .hole {
            width: 18px;
            height: 20px;
            background: var(--dark);
            border-radius: 3px;
            flex-shrink: 0;
            border: 1px solid #2a2a3a;
        }

        @keyframes scroll-strip {
            from { transform: translateX(0); }
            to   { transform: translateX(-300px); }
        }

        /* Logo */
        .logo-bar {
            text-align: center;
            padding: 32px 0 20px;
            position: relative;
            z-index: 1;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            font-weight: 700;
            color: var(--gold);
            letter-spacing: 6px;
            text-transform: uppercase;
        }

        .logo span {
            color: var(--red);
        }

        .logo-sub {
            font-size: 11px;
            letter-spacing: 4px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Main layout */
        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .auth-container {
            display: flex;
            gap: 0;
            width: 100%;
            max-width: 860px;
            border: 1px solid #2a2a3a;
            border-radius: 16px;
            overflow: hidden;
            background: var(--dark2);
            box-shadow: 0 40px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(201,168,76,0.1);
        }

        /* Tabs selector */
        .tab-selector {
            display: none;
        }

        /* Panels */
        .panel {
            flex: 1;
            padding: 40px 36px;
            position: relative;
            transition: background 0.3s;
        }

        .panel.login-panel {
            border-right: 1px solid #2a2a3a;
            background: var(--dark3);
        }

        .panel.register-panel {
            background: var(--dark2);
        }

        .panel-label {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text);
        }

        .panel-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        /* Divider vertical */
        .divider {
            width: 1px;
            background: linear-gradient(to bottom, transparent, var(--gold), transparent);
            flex-shrink: 0;
            position: relative;
        }

        .divider::after {
            content: 'OU';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--dark2);
            color: var(--gold);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 2px;
            padding: 8px 4px;
            writing-mode: vertical-rl;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 11px 14px;
            background: var(--dark4);
            border: 1px solid #2e2e42;
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,168,76,0.12);
        }

        .form-group input::placeholder {
            color: #4a4a5e;
        }

        /* 2 colonnes pour register */
        .form-row {
            display: flex;
            gap: 10px;
        }
        .form-row .form-group { flex: 1; }

        /* Boutons */
        .btn-primary {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--dark);
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: 6px;
            transition: opacity 0.2s, transform 0.1s;
        }

        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        .btn-register {
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
        }

        .btn-register:hover {
            background: rgba(201,168,76,0.1);
        }

        /* Messages erreur/succès */
        .msg {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .msg-error {
            background: rgba(230,57,70,0.12);
            border: 1px solid rgba(230,57,70,0.3);
            color: #ff6b74;
        }

        .msg-success {
            background: rgba(42,157,143,0.12);
            border: 1px solid rgba(42,157,143,0.3);
            color: #4ecdc4;
        }

        /* Film strip bottom */
        .filmstrip-bottom {
            border-top: 2px solid var(--gold);
            border-bottom: none;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .auth-container { flex-direction: column; }
            .panel.login-panel { border-right: none; border-bottom: 1px solid #2a2a3a; }
            .divider { display: none; }
            .form-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>

<!-- Film strip top -->
<div class="filmstrip">
    <div class="filmstrip-holes">
        <?php for($i=0;$i<30;$i++): ?>
            <div class="hole"></div>
        <?php endfor; ?>
    </div>
</div>

<!-- Logo -->
<div class="logo-bar">
    <div class="logo">Ciné<span>Max</span></div>
    <div class="logo-sub">Votre salle de cinéma en ligne</div>
</div>

<!-- Auth container -->
<div class="auth-wrapper">
    <div class="auth-container">

        <!-- LOGIN -->
        <div class="panel login-panel">
            <div class="panel-label">Bon retour 🎬</div>
            <div class="panel-sub">Connectez-vous à votre compte</div>

            <?php if (!empty($login_error)): ?>
                <div class="msg msg-error"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>

            <form method="POST" action="auth.php">
                <input type="hidden" name="form_type" value="login">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="votre@email.com" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-primary">Se connecter</button>
            </form>
        </div>

        <!-- Divider -->
        <div class="divider"></div>

        <!-- REGISTER -->
        <div class="panel register-panel">
            <div class="panel-label">Nouveau ici ? 🎟️</div>
            <div class="panel-sub">Créez votre compte gratuitement</div>

            <?php if (!empty($register_error)): ?>
                <div class="msg msg-error"><?= htmlspecialchars($register_error) ?></div>
            <?php endif; ?>
            <?php if (!empty($register_success)): ?>
                <div class="msg msg-success"><?= htmlspecialchars($register_success) ?></div>
            <?php endif; ?>

            <form method="POST" action="auth.php">
                <input type="hidden" name="form_type" value="register">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" placeholder="Dupont" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" placeholder="Jean" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="votre@email.com" required>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" placeholder="+33 6 00 00 00 00">
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-primary btn-register">Créer mon compte</button>
            </form>
        </div>

    </div>
</div>

<!-- Film strip bottom -->
<div class="filmstrip filmstrip-bottom">
    <div class="filmstrip-holes">
        <?php for($i=0;$i<30;$i++): ?>
            <div class="hole"></div>
        <?php endfor; ?>
    </div>
</div>

</body>
</html>