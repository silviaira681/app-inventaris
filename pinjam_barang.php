<?php
// Proses pinjam barang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pinjam'])) {
    $id_barang = (int)$_POST['id_barang'];
    $peminjam = mysqli_real_escape_string($conn, $_POST['peminjam']);
    $jumlah_pinjam = (int)$_POST['jumlah_pinjam'];
    $tanggal_pinjam = mysqli_real_escape_string($conn, $_POST['tanggal_pinjam']);
    
    // Cek stok
    $cek_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT jumlah FROM barang WHERE id_barang='$id_barang'"));
    
    if ($cek_stok['jumlah'] >= $jumlah_pinjam) {
        // Mulai transaksi
        mysqli_begin_transaction($conn);
        
        // Insert peminjaman
        mysqli_query($conn, "INSERT INTO peminjaman (id_barang, peminjam, jumlah_pinjam, tanggal_pinjam, user_id) 
                            VALUES ('$id_barang', '$peminjam', '$jumlah_pinjam', '$tanggal_pinjam', '{$_SESSION['user_id']}')");
        
        // Kurangi stok (STOK BERKURANG OTOMATIS)
        mysqli_query($conn, "UPDATE barang SET jumlah = jumlah - $jumlah_pinjam WHERE id_barang='$id_barang'");
        
        mysqli_commit($conn);
        echo "<div class='alert alert-success'>✅ Peminjaman berhasil! Stok berkurang otomatis.</div>";
    } else {
        echo "<div class='alert alert-error'>❌ Stok tidak mencukupi! Stok tersedia: " . $cek_stok['jumlah'] . "</div>";
    }
}

// Data peminjaman aktif
$peminjaman = mysqli_query($conn, "SELECT p.*, b.kode_barang, b.nama_barang 
    FROM peminjaman p 
    JOIN barang b ON p.id_barang = b.id_barang 
    WHERE p.status_peminjaman = 'dipinjam' 
    ORDER BY p.tanggal_pinjam DESC");
?>

<h2>📝 Form Peminjaman Barang</h2>

<div class="card">
    <form method="POST">
        <div class="form-group">
            <label>Pilih Barang *</label>
            <select name="id_barang" id="pilih_barang" required>
                <option value="">-- Pilih Barang --</option>
                <?php 
                $barang = mysqli_query($conn, "SELECT * FROM barang WHERE status='baik' AND jumlah > 0");
                while ($b = mysqli_fetch_assoc($barang)) {
                    echo "<option value='{$b['id_barang']}' data-stok='{$b['jumlah']}'>{$b['nama_barang']} (Stok: {$b['jumlah']})</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Nama Peminjam *</label>
            <input type="text" name="peminjam" required placeholder="Masukkan nama peminjam">
        </div>
        <div class="form-group">
            <label>Jumlah Pinjam *</label>
            <input type="number" name="jumlah_pinjam" id="jumlah_pinjam" required min="1">
            <small id="info_stok" style="color: #666;"></small>
        </div>
        <div class="form-group">
            <label>Tanggal Pinjam *</label>
            <input type="date" name="tanggal_pinjam" value="<?= date('Y-m-d') ?>" required>
        </div>
        <button type="submit" name="pinjam" class="btn btn-primary">📝 Pinjam Barang</button>
    </form>
</div>

<h3>📋 Data Peminjaman Aktif</h3>
<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr><th>ID</th><th>Kode</th><th>Barang</th><th>Peminjam</th><th>Jumlah</th><th>Tgl Pinjam</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($peminjaman)): ?>
            <tr>
                <td><?= $row['id_peminjaman'] ?></td>
                <td><?= $row['kode_barang'] ?></td>
                <td><?= $row['nama_barang'] ?></td>
                <td><?= $row['peminjam'] ?></td>
                <td><?= $row['jumlah_pinjam'] ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
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
        if (stok) {
            infoStok.innerHTML = `Maksimal pinjam: ${stok} unit`;
            jumlahPinjam.max = stok;
            jumlahPinjam.value = 1;
        } else {
            infoStok.innerHTML = '';
        }
    });
</script>