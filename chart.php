<?php
session_save_path(__DIR__ . '/sessions');
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT c.name AS category_name, t.type, SUM(t.amount) AS total
    FROM transactions t
    JOIN categories c ON t.category_id = c.category_id
    WHERE t.user_id = ?
    GROUP BY c.name, t.type
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$category_data = ['支出' => [], '收入' => []];
while ($row = $result->fetch_assoc()) {
    $type = $row['type'] == 1 ? '支出' : '收入';
    $category_data[$type][] = ['label' => $row['category_name'], 'value' => $row['total']];
}
$stmt->close();

$sql = "
    SELECT date, type, SUM(amount) AS total
    FROM transactions
    WHERE user_id = ?
    GROUP BY date, type
    ORDER BY date ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$trend_data = [];
$dates = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['date'];
    $type = $row['type'] == 1 ? '支出' : '收入';
    $total = (float)$row['total'];
    if (!isset($trend_data[$date])) $trend_data[$date] = ['支出' => 0, '收入' => 0];
    $trend_data[$date][$type] = $total;
}
$stmt->close();

$labels = [];
$income_values = [];
$expense_values = [];
$balance_values = [];
$balance = 0;
foreach ($trend_data as $date => $data) {
    $labels[] = $date;
    $income = $data['收入'];
    $expense = $data['支出'];
    $income_values[] = $income;
    $expense_values[] = $expense;
    $balance += $income - $expense;
    $balance_values[] = $balance;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>統計圖表</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    body {
      background-color: #f4fdf4;
    }
    .top-bar {
      background-color: #198754;
      color: white;
      padding: 1rem;
    }
    .top-bar .username {
      font-weight: bold;
    }
    .chart-container {
      background: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .nav-tabs .nav-link.active {
      background-color: #198754;
      color: white;
    }
    canvas {
      background-color: #fff;
      border-radius: 5px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
  </style>
</head>
<body>
<div class="top-bar d-flex justify-content-between align-items-center">
  <div class="fs-4">統計圖表</div>
  <div>
    <span class="username"><?= htmlspecialchars($_SESSION['name']) ?></span>
    <a href="logout.php" class="btn btn-sm btn-light ms-3">登出</a>
  </div>
</div>
<div class="container py-4">
  <div class="mb-3 text-end">
    <a href="index.php" class="btn btn-secondary">回首頁</a>
    <button class="btn btn-outline-danger" onclick="exportChartsToPDF()">匯出 PDF</button>
  </div>
  <div class="chart-container">
    <ul class="nav nav-tabs mb-4" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pie-chart">圓餅圖</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bar-chart">長條圖</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#line-chart">時間趨勢</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#balance-chart">累積餘額</button></li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="pie-chart">
        <div class="row">
          <?php foreach ($category_data as $type => $items): ?>
          <div class="col-md-6 mb-4">
            <h5 class="text-center"><?= $type ?>分類</h5>
            <canvas id="pie-<?= $type ?>"></canvas>
            <script>
              new Chart(document.getElementById('pie-<?= $type ?>'), {
                type: 'pie',
                data: {
                  labels: <?= json_encode(array_column($items, 'label')) ?>,
                  datasets: [{
                    data: <?= json_encode(array_column($items, 'value')) ?>,
                    borderWidth: 1
                  }]
                },
                options: { plugins: { legend: { position: 'bottom' } } }
              });
            </script>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="tab-pane fade" id="bar-chart">
        <div class="row">
          <?php foreach ($category_data as $type => $items): ?>
          <div class="col-md-6 mb-4">
            <h5 class="text-center"><?= $type ?>分類</h5>
            <canvas id="bar-<?= $type ?>"></canvas>
            <script>
              new Chart(document.getElementById('bar-<?= $type ?>'), {
                type: 'bar',
                data: {
                  labels: <?= json_encode(array_column($items, 'label')) ?>,
                  datasets: [{
                    label: '金額',
                    data: <?= json_encode(array_column($items, 'value')) ?>,
                    borderWidth: 1
                  }]
                },
                options: { plugins: { legend: { display: false } } }
              });
            </script>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="tab-pane fade" id="line-chart">
        <h5 class="text-center">每日收支趨勢</h5>
        <canvas id="line-trend"></canvas>
        <script>
          new Chart(document.getElementById('line-trend'), {
            type: 'line',
            data: {
              labels: <?= json_encode($labels) ?>,
              datasets: [
                { label: '收入', data: <?= json_encode($income_values) ?>, borderColor: 'green', fill: false },
                { label: '支出', data: <?= json_encode($expense_values) ?>, borderColor: 'red', fill: false }
              ]
            }
          });
        </script>
      </div>

      <div class="tab-pane fade" id="balance-chart">
        <h5 class="text-center">累積餘額</h5>
        <canvas id="balance-trend"></canvas>
        <script>
          new Chart(document.getElementById('balance-trend'), {
            type: 'line',
            data: {
              labels: <?= json_encode($labels) ?>,
              datasets: [{
                label: '餘額',
                data: <?= json_encode($balance_values) ?>,
                borderColor: 'blue',
                fill: false
              }]
            }
          });
        </script>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function exportChartsToPDF() {
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF();
  const canvases = document.querySelectorAll('canvas');

  for (let i = 0; i < canvases.length; i++) {
    const canvas = canvases[i];
    const imgData = canvas.toDataURL('image/png');
    if (i > 0) pdf.addPage();
    pdf.addImage(imgData, 'PNG', 15, 20, 180, 100);
  }

  const filename = `charts-${new Date().toISOString().split('T')[0]}.pdf`;
  pdf.save(filename);
}
</script>
</body>
</html>
