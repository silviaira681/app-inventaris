<?php
$id_peminjaman = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Proses pengembalian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kembalikan'])) {
    $id = (int)$_POST['id_peminjaman'];
    $kondisi = mysqli_real_escape_string($conn, $_POST['kondisi_barang']);
    $tgl_kembali = mysqli_real_escape_string($conn, $_POST['tanggal_kembali']);
    $denda = (float)$_POST['denda'];
    
    // Ambil data peminjaman
    $peminjaman = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peminjaman WHERE id_peminjaman='$id'"));
    
    if ($peminjaman) {
        mysqli_begin_transaction($conn);
        
        // Insert pengembalian
        mysqli_query($conn, "INSERT INTO pengembalian (id_peminjaman, tanggal_kembali, kondisi_barang, denda, user_id) 
                            VALUES ('$id', '$tgl_kembali', '$kondisi', '$denda', '{$_SESSION['user_id']}')");
        
        // Update status peminjaman
        mysqli_query($conn, "UPDATE peminjaman SET status_peminjaman='dikembalikan', tanggal_kembali='$tgl_kembali' 
                            WHERE id_peminjaman='$id'");
        
        // Update stok (STOK BERTAMBAH OTOMATIS)
        $update_stok = "UPDATE barang SET jumlah = jumlah + {$peminjaman['jumlah_pinjam']}";
        if ($kondisi == 'rusak') {
            $update_stok .= ", status='rusak'"; // Status berubah jadi rusak
        }
        $update_stok .= " WHERE id_barang='{$peminjaman['id_barang']}'";
        mysqli_query($conn, $update_stok);
        
        mysqli_commit($conn);
        echo "<div class='alert alert-success'>✅ Pengembalian berhasil! Stok bertambah otomatis.</div>";
        echo "<script>setTimeout(()=>{window.location.href='?page=pinjam_barang'},1500);</script>";
    }
}

// Ambil data peminjaman yang akan dikembalikan
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, b.nama_barang, b.kode_barang 
    FROM peminjaman p 
    JOIN barang b ON p.id_barang = b.id_barang 
    WHERE p.id_peminjaman='$id_peminjaman'"));

// Riwayat pengembalian
$riwayat = mysqli_query($conn, "SELECT peng.*, p.peminjam, p.tanggal_pinjam, b.nama_barang, b.kode_barang 
    FROM pengembalian peng 
    JOIN peminjaman p ON peng.id_peminjaman = p.id_peminjaman 
    JOIN barang b ON p.id_barang = b.id_barang 
    ORDER BY peng.tanggal_kembali DESC LIMIT 20");
?>

<h2>🔄 Form Pengembalian Barang</h2>

<?php if ($data && $data['status_peminjaman'] == 'dipinjam'): ?>
<div class="card">
    <form method="POST">
        <input type="hidden" name="id_peminjaman" value="<?= $id_peminjaman ?>">
        
        <div class="form-group">
            <label>Informasi Peminjaman</label>
            <div style="background: #e9ecef; padding: 10px; border-radius: 5px;">
                <strong>Kode Barang:</strong> <?= $data['kode_barang'] ?><br>
                <strong>Nama Barang:</strong> <?= $data['nama_barang'] ?><br>
                <strong>Peminjam:</strong> <?= $data['peminjam'] ?><br>
                <strong>Jumlah Pinjam:</strong> <?= $data['jumlah_pinjam'] ?><br>
                <strong>Tanggal Pinjam:</strong> <?= $data['tanggal_pinjam'] ?>
            </div>
        </div>
        
        <div class="form-group">
            <label>Kondisi Barang Saat Dikembalikan *</label>
            <select name="kondisi_barang" required>
                <option value="baik">Baik</option>
                <option value="rusak">Rusak</option>
            </select>
            <small>⚠️ Jika kondisi "Rusak", status barang akan berubah menjadi rusak</small>
        </div>
        
        <div class="form-group">
            <label>Tanggal Kembali *</label>
            <input type="date" name="tanggal_kembali" value="<?= date('Y-m-d') ?>" required>
        </div>
        
        <div class="form-group">
            <label>Denda (jika ada)</label>
            <input type="number" name="denda" step="1000" value="0" placeholder="Rp">
        </div>
        
        <button type="submit" name="kembalikan" class="btn btn-primary">✅ Proses Pengembalian</button>
        <a href="?page=pinjam_barang" class="btn btn-warning">← Kembali</a>
    </form>
</div>
<?php else: ?>
<div class="alert alert-error">
    ⚠️ Data peminjaman tidak ditemukan atau sudah dikembalikan! 
    <a href="?page=pinjam_barang">Lihat peminjaman aktif</a>
</div>
<?php endif; ?>

<h3>📋 Riwayat Pengembalian</h3>
<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr><th>ID</th><th>Barang</th><th>Peminjam</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Kondisi</th><th>Denda</th></tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>
            <tr>
                <td><?= $row['id_pengembalian'] ?></td>
                <td><?= $row['kode_barang'] ?> - <?= $row['nama_barang'] ?></td>
                <td><?= $row['peminjam'] ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></td>
                <td class="status-<?= $row['kondisi_barang'] ?>"><?= $row['kondisi_barang'] ?></td>
                <td>Rp <?= number_format($row['denda'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>