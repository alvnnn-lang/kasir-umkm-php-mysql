<?php
require "../koneksi.php";

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Filter data
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$pembayaran_filter = isset($_GET['pembayaran']) ? $_GET['pembayaran'] : '';

// DELETE - Hapus transaksi penjualan
if (isset($_GET['hapus_transaksi'])) {
    $id = intval($_GET['hapus_transaksi']);
    
    // Ambil data penjualan untuk kembalikan stok
    $stmt = mysqli_prepare($con, "SELECT produk_id, jumlah_terjual FROM penjualan_harian WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $penjualan = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($penjualan) {
        // Kembalikan stok produk
        $stmt = mysqli_prepare($con, "UPDATE produk SET stok = stok + ?, diperbarui_pada = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $penjualan['jumlah_terjual'], $penjualan['produk_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Hapus transaksi
        $stmt = mysqli_prepare($con, "DELETE FROM penjualan_harian WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    header("Location: laporanpenjualan.php?success=deleted");
    exit;
}

// UPDATE - Edit transaksi penjualan
if (isset($_POST['edit_transaksi'])) {
    $id = intval($_POST['id']);
    $jumlah_baru = intval($_POST['jumlah_terjual']);
    $pembayaran_id = intval($_POST['id_pembayaran']);
    
    // Ambil data lama
    $stmt = mysqli_prepare($con, "SELECT ph.*, p.harga FROM penjualan_harian ph JOIN produk p ON ph.produk_id = p.id WHERE ph.id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $penjualan_lama = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($penjualan_lama) {
        $selisih = $jumlah_baru - $penjualan_lama['jumlah_terjual'];
        $total_baru = $penjualan_lama['harga'] * $jumlah_baru;
        
        // Update transaksi dengan metode pembayaran
        $stmt = mysqli_prepare($con, "UPDATE penjualan_harian SET jumlah_terjual=?, total_harga=?, id_pembayaran=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "idii", $jumlah_baru, $total_baru, $pembayaran_id, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Update stok produk
        $stmt = mysqli_prepare($con, "UPDATE produk SET stok = stok - ?, diperbarui_pada = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $selisih, $penjualan_lama['produk_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    header("Location: laporanpenjualan.php?success=updated");
    exit;
}

// READ - Ambil data penjualan dengan informasi pembayaran
$query = "SELECT ph.*, p.nama as produk_nama, p.harga, k.nama as kategori_nama, pm.jenis_pembayaran 
          FROM penjualan_harian ph 
          JOIN produk p ON ph.produk_id = p.id 
          JOIN kategori k ON p.kategori_id = k.id 
          LEFT JOIN pembayaran pm ON ph.id_pembayaran = pm.id_pembayaran
          WHERE DATE(ph.tanggal_penjualan) BETWEEN ? AND ?";

$params = [$start_date, $end_date];
$types = "ss";

if (!empty($kategori_filter)) {
    $query .= " AND k.id = ?";
    $params[] = $kategori_filter;
    $types .= "i";
}

if (!empty($pembayaran_filter)) {
    $query .= " AND pm.id_pembayaran = ?";
    $params[] = $pembayaran_filter;
    $types .= "i";
}

$query .= " ORDER BY ph.waktu_penjualan DESC";

$stmt = mysqli_prepare($con, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $penjualan_result = mysqli_stmt_get_result($stmt);
    
    // Hitung statistik
    $total_sales = 0;
    $total_transactions = 0;
    $total_items = 0;
    $salesData = [];
    $payment_stats = [];
    
    while ($row = mysqli_fetch_assoc($penjualan_result)) {
        $total_sales += $row['total_harga'];
        $total_transactions++;
        $total_items += $row['jumlah_terjual'];
        $salesData[] = $row;
        
        // Statistik per metode pembayaran
        $payment_method = $row['jenis_pembayaran'] ?? 'Tidak Diketahui';
        if (!isset($payment_stats[$payment_method])) {
            $payment_stats[$payment_method] = ['count' => 0, 'total' => 0];
        }
        $payment_stats[$payment_method]['count']++;
        $payment_stats[$payment_method]['total'] += $row['total_harga'];
    }
    
    mysqli_stmt_close($stmt);
} else {
    $salesData = [];
    $total_sales = 0;
    $total_transactions = 0;
    $total_items = 0;
    $payment_stats = [];
}

// Ambil data kategori untuk filter
$kategori_result = mysqli_query($con, "SELECT * FROM kategori ORDER BY nama");

// Ambil data metode pembayaran untuk filter
$pembayaran_result = mysqli_query($con, "SELECT * FROM pembayaran ORDER BY jenis_pembayaran");

// Format rupiah
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Handle download Word
if (isset($_POST['download_type']) && $_POST['download_type'] === 'word') {
    $currentDate = date('d/m/Y');
    $content = generateReportContent($salesData, $total_sales, $total_transactions, $total_items, $payment_stats, $currentDate);
    
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="laporan-penjualan-' . date('Y-m-d') . '.doc"');
    echo $content;
    exit;
}

function generateReportContent($salesData, $total_sales, $total_transactions, $total_items, $payment_stats, $currentDate) {
    $content = "LAPORAN PENJUALAN\n";
    $content .= "Tanggal: $currentDate\n";
    $content .= "=====================================\n\n";
    $content .= "RINGKASAN:\n";
    $content .= "- Total Penjualan: " . formatRupiah($total_sales) . "\n";
    $content .= "- Jumlah Transaksi: $total_transactions\n";
    $content .= "- Item Terjual: $total_items\n\n";
    
    // Tambahkan statistik pembayaran
    $content .= "STATISTIK PEMBAYARAN:\n";
    $content .= "=====================================\n";
    foreach ($payment_stats as $method => $stats) {
        $content .= "- $method: {$stats['count']} transaksi (" . formatRupiah($stats['total']) . ")\n";
    }
    
    $content .= "\nDETAIL TRANSAKSI:\n";
    $content .= "=====================================\n";
    
    foreach ($salesData as $transaction) {
        $content .= "\nWaktu: " . date('H:i', strtotime($transaction['waktu_penjualan'])) . "\n";
        $content .= "Produk: " . $transaction['produk_nama'] . " (" . $transaction['jumlah_terjual'] . "x)\n";
        $content .= "Pembayaran: " . ($transaction['jenis_pembayaran'] ?? 'Tidak Diketahui') . "\n";
        $content .= "Total: " . formatRupiah($transaction['total_harga']) . "\n";
        $content .= "-------------------------------------\n";
    }
    
    $content .= "\nTotal Keseluruhan: " . formatRupiah($total_sales) . "\n\n";
    $content .= "Laporan dibuat pada: " . date('d/m/Y H:i:s') . "\n";
    
    return $content;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Sistem Kasir</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="./style/laporanpenjualan.css">
</head>
<body>

    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <div class="system-title">
                <h1>
                    <span class="db-icon">📊</span>
                    Laporan Penjualan Toko
                </h1>
            </div>
            <div class="welcome-text">Selamat Datang di Laporan Penjualan, Kadai Uniang Abak</div>
            
            <!-- Navigation -->
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Penjualan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="laporanpenjualan.php">Laporan Penjualan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Logout</a>
                </li>
            </ul>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                $message = '';
                switch($_GET['success']) {
                    case 'deleted': $message = '✅ Transaksi berhasil dihapus!'; break;
                    case 'updated': $message = '✅ Transaksi berhasil diperbarui!'; break;
                }
                echo $message;
                ?>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Report Header -->
            <div class="report-header">
                <h2 class="report-title">
                    📈 Laporan Penjualan - <?= date('l, d F Y') ?>
                </h2>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <label>📅 Tanggal Mulai</label>
                            <input type="date" name="start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="form-group">
                            <label>📅 Tanggal Akhir</label>
                            <input type="date" name="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="form-group">
                            <label>🏷️ Kategori</label>
                            <select name="kategori">
                                <option value="">Semua Kategori</option>
                                <?php if ($kategori_result): ?>
                                    <?php while ($kategori = mysqli_fetch_assoc($kategori_result)): ?>
                                        <option value="<?= $kategori['id'] ?>" <?= $kategori_filter == $kategori['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($kategori['nama']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>💳 Metode Pembayaran</label>
                            <select name="pembayaran">
                                <option value="">Semua Metode</option>
                                <?php if ($pembayaran_result): ?>
                                    <?php while ($pembayaran = mysqli_fetch_assoc($pembayaran_result)): ?>
                                        <option value="<?= $pembayaran['id_pembayaran'] ?>" <?= $pembayaran_filter == $pembayaran['id_pembayaran'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pembayaran['jenis_pembayaran']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-filter">🔍 Filter</button>
                    </form>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card total-sales">
                        <h3>💰 Total Penjualan</h3>
                        <div class="value"><?= formatRupiah($total_sales) ?></div>
                    </div>
                    <div class="stat-card total-transactions">
                        <h3>🧾 Jumlah Transaksi</h3>
                        <div class="value"><?= $total_transactions ?></div>
                    </div>
                    <div class="stat-card total-items">
                        <h3>📦 Item Terjual</h3>
                        <div class="value"><?= $total_items ?></div>
                    </div>
                </div>
                
                <!-- Payment Statistics -->
                <?php if (!empty($payment_stats)): ?>
                <div class="payment-stats-section">
                    <h3 class="section-title">💳 Statistik Pembayaran</h3>
                    <div class="payment-stats-grid">
                        <?php foreach ($payment_stats as $method => $stats): ?>
                        <div class="payment-stat-card">
                            <div class="payment-method"><?= htmlspecialchars($method) ?></div>
                            <div class="payment-count"><?= $stats['count'] ?> transaksi</div>
                            <div class="payment-total"><?= formatRupiah($stats['total']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Transactions -->
            <div class="transactions-section">
                <div class="section-header">
                    <h3 class="section-title">📋 Detail Transaksi</h3>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="download_type" value="word">
                        <button type="submit" class="download-btn">📝 Download Word</button>
                    </form>
                </div>
                
                <?php if (empty($salesData)): ?>
                    <div class="alert alert-info">
                        ℹ️ Tidak ada data penjualan untuk periode yang dipilih.
                    </div>
                <?php else: ?>
                    <?php foreach ($salesData as $transaction): ?>
                    <div class="transaction-item">
                        <div class="transaction-header">
                            <div class="transaction-time">
                                🕒 <?= date('H:i - d/m/Y', strtotime($transaction['waktu_penjualan'])) ?>
                            </div>
                            <div class="transaction-actions">
                                <button class="btn-action btn-warning" onclick="editTransaksi(<?= $transaction['id'] ?>, '<?= htmlspecialchars($transaction['produk_nama']) ?>', <?= $transaction['jumlah_terjual'] ?>, <?= $transaction['id_pembayaran'] ?? 'null' ?>)">✏️ Edit</button>
                                <a href="?hapus_transaksi=<?= $transaction['id'] ?>" 
                                   class="btn-action btn-danger"
                                   onclick="return confirm('Yakin ingin menghapus transaksi ini?')">🗑️ Hapus</a>
                            </div>
                        </div>
                        <div class="transaction-details">
                            🍽️ <?= htmlspecialchars($transaction['produk_nama']) ?> (<?= $transaction['jumlah_terjual'] ?>x) - <?= htmlspecialchars($transaction['kategori_nama']) ?>
                        </div>
                        <div class="transaction-payment">
                            💳 <?= htmlspecialchars($transaction['jenis_pembayaran'] ?? 'Tidak Diketahui') ?>
                        </div>
                        <div class="transaction-amount">
                            💵 <?= formatRupiah($transaction['total_harga']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="total-summary">
                        <span class="label">💎 Total Keseluruhan:</span>
                        <span class="amount"><?= formatRupiah($total_sales) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Edit Transaksi -->
    <div class="modal fade" id="modalEditTransaksi" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Edit Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" id="edit_produk_nama" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Terjual</label>
                            <input type="number" class="form-control" name="jumlah_terjual" id="edit_jumlah" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <select class="form-control" name="id_pembayaran" id="edit_pembayaran" required>
                                <option value="">Pilih Metode Pembayaran</option>
                                <?php 
                                // Reset result pointer untuk modal
                                mysqli_data_seek($pembayaran_result, 0);
                                while ($pembayaran = mysqli_fetch_assoc($pembayaran_result)): 
                                ?>
                                    <option value="<?= $pembayaran['id_pembayaran'] ?>">
                                        <?= htmlspecialchars($pembayaran['jenis_pembayaran']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <input type="hidden" name="id" id="edit_transaksi_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
                        <button type="submit" name="edit_transaksi" class="btn btn-primary">💾 Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function editTransaksi(id, nama, jumlah, pembayaranId) {
            document.getElementById('edit_transaksi_id').value = id;
            document.getElementById('edit_produk_nama').value = nama;
            document.getElementById('edit_jumlah').value = jumlah;
            
            // Set selected payment method
            if (pembayaranId) {
                document.getElementById('edit_pembayaran').value = pembayaranId;
            }
            
            new bootstrap.Modal(document.getElementById('modalEditTransaksi')).show();
        }

        // Auto hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('alert-success')) {
                    alert.style.display = 'none';
                }
            });
        }, 5000);
    </script>

</body>
</html>