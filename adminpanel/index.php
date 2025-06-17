<?php
    require "../koneksi.php";

    // CREATE - Tambah produk
    if (isset($_POST['tambah_produk'])) {
        $nama = trim($_POST['nama']);
        $harga = floatval($_POST['harga']);
        $stok = intval($_POST['stok']);
        $kategori_id = intval($_POST['kategori_id']);

        $stmt = mysqli_prepare($con, "INSERT INTO produk (nama, harga, stok, kategori_id, dibuat_pada, diperbarui_pada) VALUES (?, ?, ?, ?, NOW(), NOW())");
        mysqli_stmt_bind_param($stmt, "sdii", $nama, $harga, $stok, $kategori_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        header("Location: index.php?success=added");
        exit;
    }

    // UPDATE - Edit produk
    if (isset($_POST['edit_produk'])) {
        $id = intval($_POST['id']);
        $nama = trim($_POST['nama']);
        $harga = floatval($_POST['harga']);
        $stok = intval($_POST['stok']);

        $stmt = mysqli_prepare($con, "UPDATE produk SET nama=?, harga=?, stok=?, diperbarui_pada=NOW() WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sdii", $nama, $harga, $stok, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        header("Location: index.php?success=updated");
        exit;
    }

    //delete produk
if (isset($_GET['hapus_produk'])) {
    $id = intval($_GET['hapus_produk']);
    
    // Mulai transaction
    mysqli_autocommit($con, FALSE);
    
    try {
        // Hapus data di tabel penjualan_harian yang menggunakan produk ini
        $stmt1 = mysqli_prepare($con, "DELETE FROM penjualan_harian WHERE produk_id=?");
        mysqli_stmt_bind_param($stmt1, "i", $id);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);
        
        // Jika ada tabel lain yang menggunakan produk_id, hapus juga
        // Contoh: detail_transaksi, keranjang, dll (sesuaikan dengan database Anda)
        
        // Baru hapus produk
        $stmt2 = mysqli_prepare($con, "DELETE FROM produk WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
        
        // Commit transaction
        mysqli_commit($con);
        header("Location: index.php?success=deleted");
        exit;
        
    } catch (Exception $e) {
        // Rollback jika ada error
        mysqli_rollback($con);
        header("Location: index.php?error=delete_failed");
        exit;
    } finally {
        // Reset autocommit
        mysqli_autocommit($con, TRUE);
    }
}

    // Proses penjualan dengan kategori transaksi
    if (isset($_POST['proses_penjualan'])) {
        $produk_id = intval($_POST['produk_id']);
        $jumlah_terjual = intval($_POST['jumlah_terjual']);
        $id_pembayaran = intval($_POST['id_pembayaran']);
        
        // Ambil data produk
        $stmt = mysqli_prepare($con, "SELECT nama, harga, stok FROM produk WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $produk_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($produk && $produk['stok'] >= $jumlah_terjual) {
            $total_harga = $produk['harga'] * $jumlah_terjual;
            
            // Insert ke tabel penjualan_harian dengan id_pembayaran
            $stmt = mysqli_prepare($con, "INSERT INTO penjualan_harian (produk_id, jumlah_terjual, total_harga, tanggal_penjualan, waktu_penjualan, id_pembayaran) VALUES (?, ?, ?, CURDATE(), NOW(), ?)");
            mysqli_stmt_bind_param($stmt, "iidi", $produk_id, $jumlah_terjual, $total_harga, $id_pembayaran);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Update stok produk
            $stok_baru = $produk['stok'] - $jumlah_terjual;
            $stmt = mysqli_prepare($con, "UPDATE produk SET stok=?, diperbarui_pada=NOW() WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ii", $stok_baru, $produk_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        header("Location: index.php?success=sold");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="./style/index.css">
    <title>Sistem Kasir - Penjualan</title>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-section">
            <div class="system-title">
                <h1>
                    <span class="db-icon">💽</span>
                    Kadai Uniang Abak
                </h1>
            </div>
            <div class="welcome-text">Selamat Datang di Halaman Penjualan Kadai Uniang Abak</div>
            
            <!-- Navigation -->
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Penjualan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="laporanpenjualan.php">Laporan Penjualan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Logout</a>
                </li>
            </ul>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php
                $message = '';
                switch($_GET['success']) {
                    case 'added': $message = '✅ Produk berhasil ditambahkan!'; break;
                    case 'updated': $message = '✅ Produk berhasil diperbarui!'; break;
                    case 'deleted': $message = '✅ Produk berhasil dihapus!'; break;
                    case 'sold': $message = '✅ Penjualan berhasil diproses!'; break;
                }
                echo $message;
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="main-content">
            <?php
            // READ - Ambil data kategori
            $kategori_result = mysqli_query($con, "SELECT * FROM kategori ORDER BY nama");
            
            while ($kategori = mysqli_fetch_assoc($kategori_result)):
                // READ - Ambil produk berdasarkan kategori
                $stmt = mysqli_prepare($con, "SELECT * FROM produk WHERE kategori_id = ? ORDER BY nama");
                mysqli_stmt_bind_param($stmt, "i", $kategori['id']);
                mysqli_stmt_execute($stmt);
                $produk_result = mysqli_stmt_get_result($stmt);
                
                // Tentukan icon berdasarkan nama kategori
                $icon = "🍽️";
                if (strpos(strtolower($kategori['nama']), 'minuman') !== false) {
                    if (strpos(strtolower($kategori['nama']), 'kemasan') !== false) {
                        $icon = "🥤";
                    } else {
                        $icon = "☕";
                    }
                }
            ?>
            <!-- Section untuk <?php echo htmlspecialchars($kategori['nama']); ?> -->
            <div class="menu-section">
                <div class="category-header">
                    <h3 class="category-title">
                        <span class="category-icon"><?php echo $icon; ?></span>
                        <?php echo htmlspecialchars($kategori['nama']); ?>
                    </h3>
                    
                    <!-- Form Tambah Produk -->
                    <div class="add-product-form" style="display: none;" id="form-<?php echo $kategori['id']; ?>">
                        <form method="POST" class="d-flex gap-3 align-items-end flex-wrap">
                            <div class="form-group">
                                <label>Nama Produk</label>
                                <input type="text" name="nama" class="form-input" placeholder="Masukkan nama produk" required>
                            </div>
                            <div class="form-group">
                                <label>Harga</label>
                                <input type="number" name="harga" class="form-input" placeholder="0" required>
                            </div>
                            <div class="form-group">
                                <label>Stok</label>
                                <input type="number" name="stok" class="form-input" placeholder="0" required>
                            </div>
                            <input type="hidden" name="kategori_id" value="<?php echo $kategori['id']; ?>">
                            <button type="submit" name="tambah_produk" class="btn-add">💾 Simpan</button>
                            <button type="button" class="btn-add" style="background: #6c757d;" onclick="hideForm(<?php echo $kategori['id']; ?>)">❌ Batal</button>
                        </form>
                    </div>
                    
                    <div id="add-btn-<?php echo $kategori['id']; ?>">
                        <button class="btn-add" onclick="showForm(<?php echo $kategori['id']; ?>)">➕ Tambah Menu</button>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="products-grid">
                    <?php while ($produk = mysqli_fetch_assoc($produk_result)): ?>
                    <div class="product-card">
                        <div class="product-name"><?php echo htmlspecialchars($produk['nama']); ?></div>
                        <div class="product-price">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></div>
                        <div class="stock-info">📦 Stok: <?php echo $produk['stok']; ?> porsi</div>
                        <div class="product-actions">
                            <button class="btn-action btn-primary" onclick="jualProduk(<?php echo $produk['id']; ?>, '<?php echo htmlspecialchars($produk['nama']); ?>', <?php echo $produk['harga']; ?>, <?php echo $produk['stok']; ?>)">🛒 Jual</button>
                            <button class="btn-action btn-warning" onclick="editProduk(<?php echo $produk['id']; ?>, '<?php echo htmlspecialchars($produk['nama']); ?>', <?php echo $produk['harga']; ?>, <?php echo $produk['stok']; ?>)">✏️ Edit</button>
                            <a href="?hapus_produk=<?php echo $produk['id']; ?>" 
                               class="btn-action btn-danger"
                               onclick="return confirm('Yakin ingin menghapus <?php echo htmlspecialchars($produk['nama']); ?>?')">🗑️ Hapus</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php mysqli_stmt_close($stmt); ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Modal Penjualan -->
    <div class="modal fade" id="modalPenjualan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🛒 Penjualan Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" id="nama_produk" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Harga Satuan</label>
                                    <input type="text" class="form-control" id="harga_produk" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stok Tersedia</label>
                                    <input type="text" class="form-control" id="stok_tersedia" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">💳 Metode Pembayaran</label>
                                    <select class="form-control" name="id_pembayaran" id="id_pembayaran" required>
                                        <option value="">Pilih metode pembayaran</option>
                                        <?php
                                        // Ambil data metode pembayaran
                                        $pembayaran_result = mysqli_query($con, "SELECT * FROM pembayaran ORDER BY jenis_pembayaran");
                                        while ($pembayaran = mysqli_fetch_assoc($pembayaran_result)):
                                            // Tentukan icon berdasarkan jenis pembayaran
                                            $payment_icon = "💰";
                                            $jenis_lower = strtolower($pembayaran['jenis_pembayaran']);
                                            if (strpos($jenis_lower, 'cash') !== false || strpos($jenis_lower, 'tunai') !== false) {
                                                $payment_icon = "💵";
                                            } elseif (strpos($jenis_lower, 'card') !== false || strpos($jenis_lower, 'kartu') !== false) {
                                                $payment_icon = "💳";
                                            } elseif (strpos($jenis_lower, 'transfer') !== false || strpos($jenis_lower, 'bank') !== false) {
                                                $payment_icon = "🏦";
                                            } elseif (strpos($jenis_lower, 'digital') !== false || strpos($jenis_lower, 'e-wallet') !== false || strpos($jenis_lower, 'qris') !== false) {
                                                $payment_icon = "📱";
                                            }
                                        ?>
                                        <option value="<?php echo $pembayaran['id_pembayaran']; ?>">
                                            <?php echo $payment_icon . ' ' . htmlspecialchars($pembayaran['jenis_pembayaran']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Dijual</label>
                                    <input type="number" class="form-control" name="jumlah_terjual" id="jumlah_terjual" min="1" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Total Harga</label>
                                    <input type="text" class="form-control" id="total_harga" readonly>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="produk_id" id="produk_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
                        <button type="submit" name="proses_penjualan" class="btn-modal btn-success">💰 Proses Penjualan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Produk -->
    <div class="modal fade" id="modalEditProduk" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">✏️ Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" name="nama" id="edit_nama" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <input type="number" class="form-control" name="harga" id="edit_harga" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" class="form-control" name="stok" id="edit_stok" required>
                        </div>
                        <input type="hidden" name="id" id="edit_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal btn-secondary" data-bs-dismiss="modal">❌ Batal</button>
                        <button type="submit" name="edit_produk" class="btn-modal btn-success">💾 Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Kelola Metode Pembayaran -->
    <div class="modal fade" id="modalPembayaran" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">💳 Kelola Metode Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Metode Pembayaran Tersedia:</h6>
                        <div class="payment-methods">
                            <?php
                            // Reset query untuk tampilan modal
                            $pembayaran_result = mysqli_query($con, "SELECT * FROM pembayaran ORDER BY jenis_pembayaran");
                            while ($pembayaran = mysqli_fetch_assoc($pembayaran_result)):
                                $payment_icon = "💰";
                                $jenis_lower = strtolower($pembayaran['jenis_pembayaran']);
                                if (strpos($jenis_lower, 'cash') !== false || strpos($jenis_lower, 'tunai') !== false) {
                                    $payment_icon = "💵";
                                } elseif (strpos($jenis_lower, 'card') !== false || strpos($jenis_lower, 'kartu') !== false) {
                                    $payment_icon = "💳";
                                } elseif (strpos($jenis_lower, 'transfer') !== false || strpos($jenis_lower, 'bank') !== false) {
                                    $payment_icon = "🏦";
                                } elseif (strpos($jenis_lower, 'digital') !== false || strpos($jenis_lower, 'e-wallet') !== false || strpos($jenis_lower, 'qris') !== false) {
                                    $payment_icon = "📱";
                                }
                            ?>
                            <div class="payment-item d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                                <span><?php echo $payment_icon . ' ' . htmlspecialchars($pembayaran['jenis_pembayaran']); ?></span>
                                <small class="text-muted">ID: <?php echo $pembayaran['id_pembayaran']; ?></small>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>


    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function showForm(kategoriId) {
            document.getElementById('form-' + kategoriId).style.display = 'block';
            document.getElementById('add-btn-' + kategoriId).style.display = 'none';
        }

        function hideForm(kategoriId) {
            document.getElementById('form-' + kategoriId).style.display = 'none';
            document.getElementById('add-btn-' + kategoriId).style.display = 'block';
        }

        function jualProduk(id, nama, harga, stok) {
            document.getElementById('produk_id').value = id;
            document.getElementById('nama_produk').value = nama;
            document.getElementById('harga_produk').value = 'Rp ' + harga.toLocaleString('id-ID');
            document.getElementById('stok_tersedia').value = stok + ' porsi';
            document.getElementById('jumlah_terjual').value = 1;
            document.getElementById('jumlah_terjual').max = stok;
            document.getElementById('id_pembayaran').value = '';
            
            hitungTotal(harga);
            new bootstrap.Modal(document.getElementById('modalPenjualan')).show();
        }

        function editProduk(id, nama, harga, stok) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_harga').value = harga;
            document.getElementById('edit_stok').value = stok;
            
            new bootstrap.Modal(document.getElementById('modalEditProduk')).show();
        }

        function showPaymentMethods() {
            new bootstrap.Modal(document.getElementById('modalPembayaran')).show();
        }

        function hitungTotal(harga) {
            const jumlahInput = document.getElementById('jumlah_terjual');
            const totalInput = document.getElementById('total_harga');
            
            jumlahInput.addEventListener('input', function() {
                const jumlah = parseInt(this.value) || 0;
                const total = harga * jumlah;
                totalInput.value = 'Rp ' + total.toLocaleString('id-ID');
            });
            
            const jumlah = parseInt(jumlahInput.value) || 0;
            const total = harga * jumlah;
            totalInput.value = 'Rp ' + total.toLocaleString('id-ID');
        }

        // Validasi form penjualan
        document.addEventListener('DOMContentLoaded', function() {
            const formPenjualan = document.querySelector('#modalPenjualan form');
            if (formPenjualan) {
                formPenjualan.addEventListener('submit', function(e) {
                    const idPembayaran = document.getElementById('id_pembayaran').value;
                    const jumlahTerjual = document.getElementById('jumlah_terjual').value;
                    
                    if (!idPembayaran) {
                        e.preventDefault();
                        alert('⚠️ Silakan pilih metode pembayaran terlebih dahulu!');
                        return false;
                    }
                    
                    if (!jumlahTerjual || jumlahTerjual <= 0) {
                        e.preventDefault();
                        alert('⚠️ Jumlah yang dijual harus lebih dari 0!');
                        return false;
                    }
                });
            }
        });

        // Auto hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('show')) {
                    alert.classList.remove('show');
                }
            });
        }, 5000);
    </script>

</body>
</html>