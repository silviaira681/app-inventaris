<?php
session_start();

// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'inventaris_db');
if (!$conn) die("Koneksi database gagal: " . mysqli_connect_error());

// Cek login
if(!isset($_SESSION['user_id'])) {
    if(isset($_POST['login'])) {
        $user = mysqli_real_escape_string($conn, $_POST['username']);
        $pass = md5($_POST['password']);
        $q = mysqli_query($conn, "SELECT * FROM users WHERE username='$user' AND password='$pass'");
        if(mysqli_num_rows($q) > 0) {
            $d = mysqli_fetch_assoc($q);
            $_SESSION['user_id'] = $d['id_user'];
            $_SESSION['nama_lengkap'] = $d['nama_lengkap'];
            $_SESSION['user_level'] = $d['level'];
            header("Location: index.php");
            exit();
        } else $error = "Login gagal!";
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Login</title><style>
        body{font-family:Arial;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .box{background:white;max-width:400px;margin:auto;padding:30px;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,0.3)}
        input{width:100%;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:5px}
        button{width:100%;padding:10px;background:#667eea;color:white;border:none;border-radius:5px;cursor:pointer}
        .error{color:red;text-align:center}
    </style></head>
    <body>
    <div class="box">
        <h2>📦 Login Inventaris</h2>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit" name="login">Login</button>
        </form>
        <hr>
        <small>Admin: admin / admin123<br>Petugas: petugas / petugas123</small>
    </div>
    </body>
    </html>
    <?php exit();
}

// LOGOUT
if(isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit(); }

// PROSES TAMBAH BARANG
if(isset($_POST['tambah_barang'])) {
    $kode = mysqli_real_escape_string($conn, $_POST['kode']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $jumlah = (int)$_POST['jumlah'];
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    mysqli_query($conn, "INSERT INTO barang (kode_barang, nama_barang, kategori, status, jumlah, lokasi) VALUES ('$kode','$nama','$kategori','$status','$jumlah','$lokasi')");
    $success = "Barang berhasil ditambahkan!";
}

// PROSES EDIT BARANG
if(isset($_POST['edit_barang'])) {
    $id = (int)$_POST['id_barang'];
    $kode = mysqli_real_escape_string($conn, $_POST['kode']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $jumlah = (int)$_POST['jumlah'];
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    mysqli_query($conn, "UPDATE barang SET kode_barang='$kode', nama_barang='$nama', kategori='$kategori', status='$status', jumlah='$jumlah', lokasi='$lokasi' WHERE id_barang='$id'");
    $success = "Barang berhasil diupdate!";
}

// PROSES HAPUS BARANG
if(isset($_GET['hapus_barang'])) {
    $id = (int)$_GET['hapus_barang'];
    mysqli_query($conn, "DELETE FROM barang WHERE id_barang='$id'");
    $success = "Barang berhasil dihapus!";
}

// PROSES PINJAM BARANG
if(isset($_POST['pinjam_barang'])) {
    $id_barang = (int)$_POST['id_barang'];
    $peminjam = mysqli_real_escape_string($conn, $_POST['peminjam']);
    $jumlah = (int)$_POST['jumlah'];
    $tgl = mysqli_real_escape_string($conn, $_POST['tgl']);
    
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT jumlah FROM barang WHERE id_barang='$id_barang'"));
    if($cek['jumlah'] >= $jumlah) {
        mysqli_begin_transaction($conn);
        mysqli_query($conn, "INSERT INTO peminjaman (id_barang, peminjam, jumlah_pinjam, tanggal_pinjam, user_id) VALUES ('$id_barang','$peminjam','$jumlah','$tgl','{$_SESSION['user_id']}')");
        mysqli_query($conn, "UPDATE barang SET jumlah = jumlah - $jumlah WHERE id_barang='$id_barang'");
        mysqli_commit($conn);
        $success = "✅ Peminjaman berhasil! Stok berkurang otomatis.";
    } else {
        $error = "❌ Stok tidak mencukupi! Stok tersedia: " . $cek['jumlah'];
    }
}

// PROSES KEMBALI BARANG
if(isset($_GET['proses_kembali'])) {
    $id = (int)$_GET['proses_kembali'];
    $kondisi = mysqli_real_escape_string($conn, $_GET['kondisi']);
    $tgl_kembali = date('Y-m-d');
    
    $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peminjaman WHERE id_peminjaman='$id'"));
    if($p) {
        mysqli_begin_transaction($conn);
        mysqli_query($conn, "INSERT INTO pengembalian (id_peminjaman, tanggal_kembali, kondisi_barang) VALUES ('$id','$tgl_kembali','$kondisi')");
        mysqli_query($conn, "UPDATE peminjaman SET status_peminjaman='dikembalikan', tanggal_kembali='$tgl_kembali' WHERE id_peminjaman='$id'");
        $update = "UPDATE barang SET jumlah = jumlah + {$p['jumlah_pinjam']}";
        if($kondisi == 'rusak') $update .= ", status='rusak'";
        $update .= " WHERE id_barang='{$p['id_barang']}'";
        mysqli_query($conn, $update);
        mysqli_commit($conn);
        $success = "✅ Pengembalian berhasil! Stok bertambah otomatis.";
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Inventaris Barang</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { font-size: 1.5em; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-name { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        .nav {
            background: #f8f9fa;
            padding: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .nav a {
            background: #667eea;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .nav a:hover, .nav a.active { background: #764ba2; transform: translateY(-2px); }
        .content { padding: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #dee2e6; }
        th, td { padding: 12px; text-align: left; }
        th { background: #667eea; color: white; }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
            text-decoration: none;
        }
        .btn-primary { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;
        }
        .status-baik { color: #28a745; font-weight: bold; }
        .status-rusak { color: #dc3545; font-weight: bold; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px; border-radius: 10px; color: white; text-align: center;
        }
        .stat-card .number { font-size: 2em; font-weight: bold; }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .edit-form { background: #fff3cd; border: 1px solid #ffecb5; }
        @media print { .nav, .btn, form, .no-print, .user-info { display: none; } }
        @media (max-width: 768px) { .header { flex-direction: column; text-align: center; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📦 Aplikasi Pengelolaan Barang Inventaris</h1>
        <div class="user-info">
            <span class="user-name">👤 <?= $_SESSION['nama_lengkap'] ?> (<?= $_SESSION['user_level'] ?>)</span>
            <a href="?logout=1" class="btn-logout">🚪 Logout</a>
        </div>
    </div>
    <div class="nav">
        <a href="?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="?page=tambah_barang" class="<?= $page == 'tambah_barang' ? 'active' : '' ?>">📦 Tambah Barang</a>
        <a href="?page=pinjam_barang" class="<?= $page == 'pinjam_barang' ? 'active' : '' ?>">📝 Pinjam Barang</a>
        <a href="?page=pengembalian" class="<?= $page == 'pengembalian' ? 'active' : '' ?>">🔄 Pengembalian</a>
        <a href="?page=laporan" class="<?= $page == 'laporan' ? 'active' : '' ?>">📄 Laporan</a>
    </div>
    <div class="content">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <script>setTimeout(()=>{document.querySelector('.alert').style.display='none'},3000);</script>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <script>setTimeout(()=>{document.querySelector('.alert').style.display='none'},3000);</script>
        <?php endif; ?>

        <?php
        // ==================== DASHBOARD ====================
        if($page == 'dashboard') {
            $total_barang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM barang"))['total'];
            $total_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM barang"))['total'];
            $barang_baik = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM barang WHERE status='baik'"))['total'];
            $barang_rusak = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM barang WHERE status='rusak'"))['total'];
            $sedang_dipinjam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status_peminjaman='dipinjam'"))['total'];
            ?>
            <h2>📊 Dashboard Inventaris</h2>
            <div class="stats">
                <div class="stat-card"><h3>Total Jenis Barang</h3><div class="number"><?= $total_barang ?></div></div>
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);"><h3>Total Stok</h3><div class="number"><?= $total_stok ?></div></div>
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);"><h3>Barang Baik</h3><div class="number"><?= $barang_baik ?></div></div>
                <div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);"><h3>Barang Rusak</h3><div class="number"><?= $barang_rusak ?></div></div>
                <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);"><h3>Sedang Dipinjam</h3><div class="number"><?= $sedang_dipinjam ?></div></div>
            </div>
            <div class="card">
                <h3>📌 Informasi Sistem</h3>
                <ul>
                    <li>✅ Stok barang akan <strong>berkurang otomatis</strong> saat peminjaman</li>
                    <li>✅ Stok barang akan <strong>bertambah otomatis</strong> saat pengembalian</li>
                    <li>✅ Status barang otomatis berubah menjadi "rusak" jika dikembalikan dalam kondisi rusak</li>
                </ul>
            </div>
            <?php
        }
        // ==================== TAMBAH & EDIT BARANG ====================
        elseif($page == 'tambah_barang') {
            // Ambil data untuk edit jika ada parameter edit
            $edit_data = null;
            if(isset($_GET['edit'])) {
                $id_edit = (int)$_GET['edit'];
                $edit_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM barang WHERE id_barang='$id_edit'"));
            }
            
            $barang = mysqli_query($conn, "SELECT * FROM barang ORDER BY id_barang DESC");
            ?>
            <h2>📦 <?= $edit_data ? 'Edit Barang' : 'Tambah Barang' ?></h2>
            
            <!-- Form Tambah/Edit Barang -->
            <div class="card <?= $edit_data ? 'edit-form' : '' ?>">
                <h3><?= $edit_data ? '✏️ Form Edit Barang' : '➕ Form Tambah Barang Baru' ?></h3>
                <form method="POST">
                    <?php if($edit_data): ?>
                        <input type="hidden" name="id_barang" value="<?= $edit_data['id_barang'] ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Kode Barang *</label>
                        <input type="text" name="kode" required placeholder="Contoh: BRG001" value="<?= $edit_data['kode_barang'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Nama Barang *</label>
                        <input type="text" name="nama" required placeholder="Contoh: Laptop ASUS" value="<?= $edit_data['nama_barang'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <input type="text" name="kategori" placeholder="Contoh: Elektronik" value="<?= $edit_data['kategori'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="baik" <?= ($edit_data['status'] ?? '') == 'baik' ? 'selected' : '' ?>>Baik</option>
                            <option value="rusak" <?= ($edit_data['status'] ?? '') == 'rusak' ? 'selected' : '' ?>>Rusak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jumlah *</label>
                        <input type="number" name="jumlah" required min="1" value="<?= $edit_data['jumlah'] ?? '1' ?>">
                    </div>
                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" placeholder="Contoh: Ruang ICT" value="<?= $edit_data['lokasi'] ?? '' ?>">
                    </div>
                    <button type="submit" name="<?= $edit_data ? 'edit_barang' : 'tambah_barang' ?>" class="btn btn-primary">
                        <?= $edit_data ? '✏️ Update Barang' : '➕ Tambah Barang' ?>
                    </button>
                    <?php if($edit_data): ?>
                        <a href="?page=tambah_barang" class="btn btn-secondary">❌ Batal Edit</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <h3>📋 Daftar Barang</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Stok</th>
                            <th>Lokasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($barang)): ?>
                        <tr>
                            <td><?= $row['kode_barang'] ?></td>
                            <td><?= $row['nama_barang'] ?></td>
                            <td><?= $row['kategori'] ?></td>
                            <td class="status-<?= $row['status'] ?>"><?= $row['status'] ?></td>
                            <td><strong><?= $row['jumlah'] ?></strong></td>
                            <td><?= $row['lokasi'] ?></td>
                            <td>
                                <a href="?page=tambah_barang&edit=<?= $row['id_barang'] ?>" class="btn btn-warning">✏️ Edit</a>
                                <a href="?page=tambah_barang&hapus_barang=<?= $row['id_barang'] ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus barang ini?')">🗑️ Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        // ==================== PINJAM BARANG ====================
        elseif($page == 'pinjam_barang') {
            $peminjaman = mysqli_query($conn, "SELECT p.*, b.kode_barang, b.nama_barang FROM peminjaman p JOIN barang b ON p.id_barang=b.id_barang WHERE p.status_peminjaman='dipinjam' ORDER BY p.tanggal_pinjam DESC");
            ?>
            <h2>📝 Pinjam Barang</h2>
            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <label>Pilih Barang</label>
                        <select name="id_barang" id="pilih_barang" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php 
                            $brg = mysqli_query($conn, "SELECT * FROM barang WHERE status='baik' AND jumlah>0");
                            while($b = mysqli_fetch_assoc($brg)) {
                                echo "<option value='{$b['id_barang']}' data-stok='{$b['jumlah']}'>{$b['nama_barang']} (Stok: {$b['jumlah']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Peminjam</label>
                        <input type="text" name="peminjam" required placeholder="Masukkan nama peminjam">
                    </div>
                    <div class="form-group">
                        <label>Jumlah Pinjam</label>
                        <input type="number" name="jumlah" id="jumlah_pinjam" min="1" required>
                        <small id="info_stok" style="color: #666;"></small>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Pinjam</label>
                        <input type="date" name="tgl" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <button type="submit" name="pinjam_barang" class="btn btn-primary">📝 Pinjam Barang</button>
                </form>
            </div>
            <h3>📋 Data Peminjaman Aktif</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kode</th>
                            <th>Barang</th>
                            <th>Peminjam</th>
                            <th>Jumlah</th>
                            <th>Tgl Pinjam</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($peminjaman)): ?>
                        <tr>
                            <td><?= $row['id_peminjaman'] ?></td>
                            <td><?= $row['kode_barang'] ?></td>
                            <td><?= $row['nama_barang'] ?></td>
                            <td><?= $row['peminjam'] ?></td>
                            <td><?= $row['jumlah_pinjam'] ?></td>
                            <td><?= $row['tanggal_pinjam'] ?></td>
                            <td><a href="?page=pengembalian&id=<?= $row['id_peminjaman'] ?>" class="btn btn-info">🔄 Kembalikan</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <script>
                const selectBarang = document.getElementById('pilih_barang');
                const jumlahPinjam = document.getElementById('jumlah_pinjam');
                const infoStok = document.getElementById('info_stok');
                
                selectBarang.addEventListener('change', function() {
                    const stok = this.options[this.selectedIndex].getAttribute('data-stok');
                    if(stok) {
                        infoStok.innerHTML = `Maksimal pinjam: ${stok} unit`;
                        jumlahPinjam.max = stok;
                        jumlahPinjam.value = 1;
                    } else {
                        infoStok.innerHTML = '';
                    }
                });
            </script>
            <?php
        }
        // ==================== PENGEMBALIAN ====================
        elseif($page == 'pengembalian') {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $data = null;
            if($id > 0) {
                $data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, b.nama_barang, b.kode_barang FROM peminjaman p JOIN barang b ON p.id_barang=b.id_barang WHERE p.id_peminjaman='$id'"));
            }
            $riwayat = mysqli_query($conn, "SELECT peng.*, p.peminjam, p.tanggal_pinjam, b.nama_barang FROM pengembalian peng JOIN peminjaman p ON peng.id_peminjaman=p.id_peminjaman JOIN barang b ON p.id_barang=b.id_barang ORDER BY peng.tanggal_kembali DESC LIMIT 20");
            ?>
            <h2>🔄 Pengembalian Barang</h2>
            <?php if($data && $data['status_peminjaman'] == 'dipinjam'): ?>
            <div class="card">
                <h3>Info Peminjaman:</h3>
                <p><strong>Barang:</strong> <?= $data['kode_barang'] ?> - <?= $data['nama_barang'] ?></p>
                <p><strong>Peminjam:</strong> <?= $data['peminjam'] ?></p>
                <p><strong>Jumlah:</strong> <?= $data['jumlah_pinjam'] ?></p>
                <p><strong>Tgl Pinjam:</strong> <?= $data['tanggal_pinjam'] ?></p>
                <hr>
                <form method="GET">
                    <input type="hidden" name="page" value="pengembalian">
                    <div class="form-group">
                        <label>Kondisi Barang</label>
                        <select name="kondisi">
                            <option value="baik">Baik</option>
                            <option value="rusak">Rusak</option>
                        </select>
                        <small>⚠️ Jika kondisi "Rusak", status barang akan berubah menjadi rusak</small>
                    </div>
                    <button type="submit" name="proses_kembali" value="<?= $id ?>" class="btn btn-primary">✅ Proses Pengembalian</button>
                    <a href="?page=pinjam_barang" class="btn btn-warning">← Kembali</a>
                </form>
            </div>
            <?php elseif($id > 0): ?>
            <div class="alert alert-error">Data peminjaman tidak ditemukan atau sudah dikembalikan! <a href="?page=pinjam_barang">Kembali ke daftar peminjaman</a></div>
            <?php else: ?>
            <div class="alert alert-error">Silakan pilih peminjaman dari daftar peminjaman aktif! <a href="?page=pinjam_barang">Lihat daftar peminjaman</a></div>
            <?php endif; ?>
            <h3>📋 Riwayat Pengembalian</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Barang</th>
                            <th>Peminjam</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Kondisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($riwayat)): ?>
                        <tr>
                            <td><?= $row['id_pengembalian'] ?></td>
                            <td><?= $row['nama_barang'] ?></td>
                            <td><?= $row['peminjam'] ?></td>
                            <td><?= $row['tanggal_pinjam'] ?></td>
                            <td><?= $row['tanggal_kembali'] ?></td>
                            <td class="status-<?= $row['kondisi_barang'] ?>"><?= $row['kondisi_barang'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        // ==================== LAPORAN ====================
        elseif($page == 'laporan') {
            $where = "";
            $tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
            $tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
            if($tgl_awal && $tgl_akhir) {
                $where = "WHERE p.tanggal_pinjam BETWEEN '$tgl_awal' AND '$tgl_akhir'";
            }
            $laporan = mysqli_query($conn, "SELECT p.*, b.kode_barang, b.nama_barang, peng.kondisi_barang, peng.tanggal_kembali as tgl_kembali 
                FROM peminjaman p 
                JOIN barang b ON p.id_barang=b.id_barang 
                LEFT JOIN pengembalian peng ON p.id_peminjaman=peng.id_peminjaman 
                $where 
                ORDER BY p.tanggal_pinjam DESC");
            ?>
            <h2>📄 Laporan Peminjaman</h2>
            <div class="card no-print">
                <form method="GET">
                    <input type="hidden" name="page" value="laporan">
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <div class="form-group" style="flex:1">
                            <label>Dari Tanggal</label>
                            <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>">
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>Sampai Tanggal</label>
                            <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>">
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">🔍 Filter</button>
                        </div>
                        <div class="form-group" style="flex:1">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-info" onclick="window.print()">🖨️ Cetak</button>
                        </div>
                    </div>
                </form>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kode</th>
                            <th>Barang</th>
                            <th>Peminjam</th>
                            <th>Jumlah</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Kondisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($laporan)): ?>
                        <tr>
                            <td><?= $row['id_peminjaman'] ?></td>
                            <td><?= $row['kode_barang'] ?></td>
                            <td><?= $row['nama_barang'] ?></td>
                            <td><?= $row['peminjam'] ?></td>
                            <td><?= $row['jumlah_pinjam'] ?></td>
                            <td><?= $row['tanggal_pinjam'] ?></td>
                            <td><?= $row['tgl_kembali'] ?? '-' ?></td>
                            <td><?= $row['status_peminjaman'] ?></td>
                            <td class="status-<?= $row['kondisi_barang'] ?>"><?= $row['kondisi_barang'] ?? '-' ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        ?>
    </div>
</div>
</body>
</html>