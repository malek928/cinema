<?php
session_start();
require_once __DIR__ . "/../config/db.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: /cinema/auth/auth.php");
    exit;
}
// 1. Films les plus réservés
$top_films = $pdo->query("
    SELECT f.titre, SUM(r.nb_places) as total_places, COUNT(r.id) as nb_reservations
    FROM reservation r
    JOIN seance s ON r.id_seance = s.id
    JOIN film f ON s.id_film = f.id
    WHERE r.statut != 'annulé'
    GROUP BY f.id, f.titre
    ORDER BY total_places DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Taux de remplissage par salle
$remplissage = $pdo->query("
    SELECT salle,
           SUM(places_total) as total,
           SUM(places_total - places_dispo) as occupees
    FROM seance
    GROUP BY salle
    ORDER BY salle
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Réservations par statut (camembert)
$par_statut = $pdo->query("
    SELECT statut, COUNT(*) as nb
    FROM reservation
    GROUP BY statut
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Réservations par jour (7 derniers jours)
$par_jour = $pdo->query("
    SELECT DATE(date_resa) as jour, COUNT(*) as nb, SUM(nb_places) as places
    FROM reservation
    WHERE date_resa >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(date_resa)
    ORDER BY jour ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 5. Stats globales
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM reservation WHERE statut != 'annulé') as total_resa,
        (SELECT SUM(nb_places) FROM reservation WHERE statut != 'annulé') as total_places,
        (SELECT COUNT(*) FROM film) as total_films,
        (SELECT COUNT(*) FROM seance) as total_seances,
        (SELECT COUNT(*) FROM user WHERE role = 'user') as total_users
")->fetch(PDO::FETCH_ASSOC);

// Préparer les données JSON pour Chart.js
$films_labels  = json_encode(array_column($top_films, "titre"));
$films_data    = json_encode(array_column($top_films, "total_places"));

$salles_labels = json_encode(array_column($remplissage, "salle"));
$salles_pct    = json_encode(array_map(fn($s) => $s["total"] > 0 ? round(($s["occupees"]/$s["total"])*100) : 0, $remplissage));

$statut_labels = json_encode(array_column($par_statut, "statut"));
$statut_data   = json_encode(array_column($par_statut, "nb"));

$jours_labels  = json_encode(array_map(fn($j) => date("d/m", strtotime($j["jour"])), $par_jour));
$jours_data    = json_encode(array_column($par_jour, "places"));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinéMax – Statistiques</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
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

        .page-title { font-family:'Playfair Display',serif; font-size:30px; margin-bottom:28px; }
        .page-title span { color:var(--gold); }

        /* KPI cards */
        .kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:32px; }
        .kpi-card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:12px; padding:20px; text-align:center; transition:border-color 0.2s; }
        .kpi-card:hover { border-color:var(--gold); }
        .kpi-icon { font-size:28px; margin-bottom:8px; }
        .kpi-num { font-family:'Playfair Display',serif; font-size:32px; color:var(--gold); line-height:1; }
        .kpi-label { font-size:12px; color:var(--text-muted); margin-top:6px; }

        /* Charts grid */
        .charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
        .chart-card { background:var(--dark2); border:1px solid #2a2a3a; border-radius:14px; padding:24px; }
        .chart-card.full { grid-column:1/-1; }
        .chart-title { font-family:'Playfair Display',serif; font-size:18px; margin-bottom:6px; }
        .chart-title span { color:var(--gold); }
        .chart-sub { font-size:12px; color:var(--text-muted); margin-bottom:20px; }
        .chart-wrap { position:relative; height:280px; }
        .chart-wrap-sm { position:relative; height:240px; }

        /* Top films table */
        .top-table { width:100%; border-collapse:collapse; margin-top:8px; }
        .top-table tr { border-bottom:1px solid #1e1e2a; }
        .top-table tr:last-child { border-bottom:none; }
        .top-table td { padding:10px 8px; font-size:14px; }
        .rank { font-family:'Playfair Display',serif; font-size:18px; color:var(--gold); width:32px; }
        .film-bar { height:6px; background:var(--dark4); border-radius:3px; margin-top:4px; overflow:hidden; }
        .film-bar-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--gold),var(--gold-light)); }
        .film-count { font-size:13px; color:var(--text-muted); text-align:right; white-space:nowrap; }
        .film-count strong { color:var(--gold); font-size:16px; display:block; }

        @media(max-width:900px) {
            .kpi-grid { grid-template-columns:repeat(3,1fr); }
            .charts-grid { grid-template-columns:1fr; }
            .chart-card.full { grid-column:1; }
            nav { padding:14px 20px; }
        }
        @media(max-width:500px) { .kpi-grid { grid-template-columns:repeat(2,1fr); } }
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
        <a href="/cinema/admin/reservations/liste.php" class="nav-link">🎟️ Réservations</a>
        <a href="/cinema/admin/stats.php" class="nav-link active">📊 Stats</a>
        <a href="/cinema/auth/logout.php" class="logout-btn">Déconnexion</a>
    </div>
</nav>

<main>
    <h1 class="page-title">Tableau de bord <span>Statistiques</span></h1>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon">🎟️</div>
            <div class="kpi-num"><?= $stats["total_resa"] ?? 0 ?></div>
            <div class="kpi-label">Réservations actives</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">💺</div>
            <div class="kpi-num"><?= $stats["total_places"] ?? 0 ?></div>
            <div class="kpi-label">Places vendues</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🎬</div>
            <div class="kpi-num"><?= $stats["total_films"] ?? 0 ?></div>
            <div class="kpi-label">Films au catalogue</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🕐</div>
            <div class="kpi-num"><?= $stats["total_seances"] ?? 0 ?></div>
            <div class="kpi-label">Séances programmées</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">👥</div>
            <div class="kpi-num"><?= $stats["total_users"] ?? 0 ?></div>
            <div class="kpi-label">Utilisateurs inscrits</div>
        </div>
    </div>

    <!-- Graphiques ligne 1 -->
    <div class="charts-grid">

        <!-- Films les plus réservés - Barres -->
        <div class="chart-card">
            <div class="chart-title">Films les plus <span>réservés</span></div>
            <div class="chart-sub">Nombre de places vendues par film</div>
            <div class="chart-wrap">
                <canvas id="chartFilms"></canvas>
            </div>
        </div>

        <!-- Statuts - Camembert -->
        <div class="chart-card">
            <div class="chart-title">Répartition des <span>statuts</span></div>
            <div class="chart-sub">Confirmées / En attente / Annulées</div>
            <div class="chart-wrap">
                <canvas id="chartStatuts"></canvas>
            </div>
        </div>

        <!-- Remplissage salles - Barres horizontales -->
        <div class="chart-card">
            <div class="chart-title">Taux de <span>remplissage</span></div>
            <div class="chart-sub">Pourcentage d'occupation par salle</div>
            <div class="chart-wrap">
                <canvas id="chartSalles"></canvas>
            </div>
        </div>

        <!-- Activité 7 jours - Ligne -->
        <div class="chart-card">
            <div class="chart-title">Activité des <span>7 derniers jours</span></div>
            <div class="chart-sub">Places réservées par jour</div>
            <div class="chart-wrap">
                <canvas id="chartJours"></canvas>
            </div>
        </div>

    </div>

    <!-- Top films détaillé -->
    <?php if (!empty($top_films)): ?>
    <div class="chart-card full" style="margin-top:0">
        <div class="chart-title">Classement <span>détaillé</span></div>
        <div class="chart-sub">Films classés par nombre de places vendues</div>
        <?php $max_places = max(array_column($top_films, "total_places")); ?>
        <table class="top-table">
            <?php foreach ($top_films as $i => $f): ?>
            <tr>
                <td class="rank">#<?= $i+1 ?></td>
                <td style="width:100%">
                    <div style="font-weight:500"><?= htmlspecialchars($f["titre"]) ?></div>
                    <div class="film-bar">
                        <div class="film-bar-fill" style="width:<?= round(($f["total_places"]/$max_places)*100) ?>%"></div>
                    </div>
                </td>
                <td class="film-count">
                    <strong><?= $f["total_places"] ?></strong>
                    places
                </td>
                <td class="film-count" style="padding-left:16px">
                    <strong><?= $f["nb_reservations"] ?></strong>
                    résa
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

</main>

<script>
// Couleurs communes
const gold    = '#c9a84c';
const goldL   = '#e8c96d';
const red     = '#e63946';
const teal    = '#2a9d8f';
const purple  = '#7b5ea7';
const blue    = '#457b9d';

Chart.defaults.color = '#8a8799';
Chart.defaults.borderColor = '#2a2a3a';
Chart.defaults.font.family = "'DM Sans', sans-serif";

// 1. Films les plus réservés (barres verticales)
new Chart(document.getElementById('chartFilms'), {
    type: 'bar',
    data: {
        labels: <?= $films_labels ?>,
        datasets: [{
            label: 'Places vendues',
            data: <?= $films_data ?>,
            backgroundColor: [gold, goldL, red, teal, purple, blue],
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.raw} places` } }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#1e1e2a' }, ticks: { color: '#8a8799' } },
            x: { grid: { display: false }, ticks: { color: '#8a8799' } }
        }
    }
});

// 2. Statuts (camembert)
new Chart(document.getElementById('chartStatuts'), {
    type: 'doughnut',
    data: {
        labels: <?= $statut_labels ?>,
        datasets: [{
            data: <?= $statut_data ?>,
            backgroundColor: [teal, gold, red],
            borderColor: '#12121a',
            borderWidth: 3,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, color: '#8a8799' } }
        },
        cutout: '60%'
    }
});

// 3. Remplissage salles (barres horizontales)
new Chart(document.getElementById('chartSalles'), {
    type: 'bar',
    data: {
        labels: <?= $salles_labels ?>,
        datasets: [{
            label: 'Remplissage (%)',
            data: <?= $salles_pct ?>,
            backgroundColor: ctx => {
                const v = ctx.raw;
                return v >= 80 ? red : v >= 50 ? gold : teal;
            },
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.raw}% rempli` } }
        },
        scales: {
            x: { beginAtZero: true, max: 100, grid: { color: '#1e1e2a' }, ticks: { callback: v => v+'%', color: '#8a8799' } },
            y: { grid: { display: false }, ticks: { color: '#8a8799' } }
        }
    }
});

// 4. Activité 7 jours (courbe)
new Chart(document.getElementById('chartJours'), {
    type: 'line',
    data: {
        labels: <?= $jours_labels ?>,
        datasets: [{
            label: 'Places réservées',
            data: <?= $jours_data ?>,
            borderColor: gold,
            backgroundColor: 'rgba(201,168,76,0.08)',
            borderWidth: 2,
            pointBackgroundColor: gold,
            pointRadius: 5,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.raw} places` } }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#1e1e2a' }, ticks: { color: '#8a8799' } },
            x: { grid: { display: false }, ticks: { color: '#8a8799' } }
        }
    }
});
</script>

</body>
</html>