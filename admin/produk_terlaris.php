<?php
// ====== KONEKSI DB ======
require_once '../config/database.php'; // pastikan $conn ada

// ====== FILTER TANGGAL ======
$awal  = $_GET['awal']  ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-t');

// ====== QUERY UPGRADE: TAMBAH HARGA, STOK, & OMZET ======
$sql = "SELECT
    p.id,
    p.name,
    p.price,
    p.stock,
    SUM(od.quantity) AS total_terjual,
    SUM(od.quantity * p.price) AS total_omzet,
    MAX(o.created_at) AS tanggal_beli
  FROM order_details od
  JOIN orders o ON o.id = od.order_id
  JOIN products p ON p.id = od.product_id
  WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status IN ('paid','settlement','capture','processing','pending','Completed')
  GROUP BY p.id
  ORDER BY total_terjual DESC
  LIMIT 10
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) die('Prepare gagal: '.mysqli_error($conn));
mysqli_stmt_bind_param($stmt, "ss", $awal, $akhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$topProducts = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Produk Terlaris</title>

  <!-- ====== CSS MURNI ====== -->
  <style>
    * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
    body { background:#f4f6f9; padding:30px; color:#333; }
    h1 { margin-bottom:15px; color:#2c3e50; }

    .filter {
      margin-bottom: 15px;
      background:#fff;
      padding:15px;
      border-radius:8px;
      max-width:1000px;
      box-shadow:0 2px 8px rgba(0,0,0,.08);
    }

    .filter input, .filter button {
      padding:8px 10px;
      border-radius:5px;
      border:1px solid #ccc;
      margin-right:8px;
    }

    .filter button {
      background:#ff4d8d;
      color:#fff;
      border:none;
      cursor:pointer;
    }

    .card {
      background:#fff;
      padding:20px;
      border-radius:8px;
      max-width:1000px;
      box-shadow:0 4px 12px rgba(0,0,0,.1);
    }

    table { width:100%; border-collapse:collapse; }
    thead { background:#ff4d8d; color:#fff; }
    th, td { padding:12px; text-align:left; font-size:14px; }
    tbody tr { border-bottom:1px solid #eee; }
    tbody tr:hover { background:#fff0f6; }

    .no { width:50px; text-align:center; font-weight:bold; }
    .qty { text-align:center; font-weight:bold; color:#ff4d8d; }
    .badge {
      background:#ff4d8d; color:#fff;
      padding:5px 12px; border-radius:20px; font-size:13px;
    }
    .date { color:#555; font-size:13px; }

    @media (max-width:768px){
      table, thead, tbody, th, td, tr { display:block; }
      thead { display:none; }
      tr { margin-bottom:12px; background:#fff; padding:12px; border-radius:6px; }
      td { border:none; padding:6px 0; }
      td::before { content:attr(data-label); font-weight:bold; display:block; color:#777; }
    }
  </style>
</head>

<body>

<h1>Produk Terlaris</h1>

<!-- ====== FILTER TANGGAL ====== -->
<form method="get" class="filter">
  <label>Dari:</label>
  <input type="date" name="awal" value="<?= htmlspecialchars($awal) ?>">
  <label>Sampai:</label>
  <input type="date" name="akhir" value="<?= htmlspecialchars($akhir) ?>">
  <button type="submit">Filter</button>
</form>

<div class="card">
  <table>
    <thead>
      <tr>
        <th class="no">#</th>
        <th>Nama Produk</th>
        <th>Stok Sisa</th>
        <th>Harga Satuan</th>
        <th style="text-align:center;">Total Terjual</th>
        <th>Total Omzet</th>
        <th>Terakhir Dibeli</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($topProducts)): ?>
        <tr><td colspan="7" style="text-align:center; padding: 20px;">Belum ada data penjualan pada periode ini</td></tr>
      <?php else: ?>
        <?php $no=1; foreach ($topProducts as $row): ?>
          <tr>
            <td class="no" data-label="#">
                <?php 
                    if($no == 1) echo "👑";
                    elseif($no == 2) echo "🥈";
                    elseif($no == 3) echo "🥉";
                    else echo $no;
                    $no++;
                ?>
            </td>
            <td data-label="Nama Produk" style="font-weight:bold; color:#2c3e50;">
                <?= htmlspecialchars($row['name']) ?>
            </td>
            <td data-label="Stok Sisa">
                <span style="color: <?= $row['stock'] < 10 ? '#e74c3c' : '#27ae60' ?>; font-weight:bold;">
                    <?= $row['stock'] ?> pcs
                </span>
            </td>
            <td data-label="Harga">
                Rp <?= number_format($row['price'], 0, ',', '.') ?>
            </td>
            <td class="qty" data-label="Total Terjual" style="text-align:center;">
              <span class="badge"><?= (int)$row['total_terjual'] ?></span>
            </td>
            <td data-label="Total Omzet" style="font-weight:bold; color:#ff4d8d;">
                Rp <?= number_format($row['total_omzet'], 0, ',', '.') ?>
            </td>
            <td class="date" data-label="Terakhir Dibeli">
              <?= date('d M Y', strtotime($row['tanggal_beli'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>