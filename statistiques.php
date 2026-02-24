<?php
session_start();
require_once "../courrier/conn.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// ================================
// STATISTIQUES PAR SERVICE
// ================================
$sql = "
SELECT 
    s.nom AS service,
    COUNT(c.id) AS total,
    SUM(CASE WHEN c.statut = 'reçu' THEN 1 ELSE 0 END) AS recus,
    SUM(CASE WHEN c.statut = 'en cours' THEN 1 ELSE 0 END) AS encours,
    SUM(CASE WHEN c.statut = 'cloturé' THEN 1 ELSE 0 END) AS clotures
FROM services s
LEFT JOIN courriers c ON c.service_actuel_id = s.id
GROUP BY s.id
ORDER BY s.nom ASC
";

$stmt = $db->query($sql);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include "../courrier/header.php"; ?>

<div class="container mt-4">

    <h3 class="mb-4">📊 Statistiques des services</h3>

    <!-- ===================== GRAPHIQUE ===================== -->
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">
            Activité des services
        </div>
        <div class="card-body">
            <canvas id="servicesChart" height="120"></canvas>
        </div>
    </div>

    <!-- ===================== TABLEAU ===================== -->
    <div class="card shadow">
        <div class="card-header bg-secondary text-white">
            Détails par service
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Service</th>
                        <th>Reçus</th>
                        <th>En cours</th>
                        <th>Clôturés</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['service']) ?></td>
                            <td class="text-primary"><?= $s['recus'] ?></td>
                            <td class="text-warning"><?= $s['encours'] ?></td>
                            <td class="text-success"><?= $s['clotures'] ?></td>
                            <td><strong><?= $s['total'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ===================== CHART JS ===================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const data = {
    labels: <?= json_encode(array_column($stats, 'service')) ?>,
    datasets: [
        {
            label: 'Reçus',
            data: <?= json_encode(array_column($stats, 'recus')) ?>,
            backgroundColor: '#0d6efd'
        },
        {
            label: 'En cours',
            data: <?= json_encode(array_column($stats, 'encours')) ?>,
            backgroundColor: '#ffc107'
        },
        {
            label: 'Clôturés',
            data: <?= json_encode(array_column($stats, 'clotures')) ?>,
            backgroundColor: '#198754'
        }
    ]
};

new Chart(document.getElementById('servicesChart'), {
    type: 'bar',
    data: data,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            title: {
                display: true,
                text: 'Performance des services'
            }
        }
    }
});
</script>

<?php include "../courrier/footer.php"; ?>
