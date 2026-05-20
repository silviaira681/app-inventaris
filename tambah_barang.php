<?php
// Proses tambah barang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $kode = mysqli_real_escape_string($conn, $_POST['kode_barang']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $jumlah = (int)$_POST['jumlah'];
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    
    $query = "INSERT INTO barang (kode_barang, nama_barang, kategori, status, jumlah, lokasi) 
              VALUES ('$kode', '$nama', '$kategori', '$status', '$jumlah', '$lokasi')";
    
    if (mysqli_query($conn, $query)) {
        echo "<div class='alert alert-success'>✅ Barang berhasil ditambahkan!</div>";
    } else {
        echo "<div class='alert alert-error'>❌ Gagal: " . mysqli_error($conn) . "</div>";
    }
}

// Proses hapus barang
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM barang WHERE id_barang='$id'");
    echo "<div class='alert alert-success'>🗑️ Barang berhasil dihapus!</div>";
}

$barang = mysqli_query($conn, "SELECT * FROM barang ORDER BY id_barang DESC");
?>

<h2>📦 Tambah Barang Inventaris</h2>

<div class="card">
    <h3>Form Tambah Barang</h3>
    <form method="POST">
        <div class="form-group">
            <label>Kode Barang *</label>
            <input type="text" name="kode_barang" required placeholder="Contoh: BRG001">
        </div>
        <div class="form-group">
            <label>Nama Barang *</label>
            <input type="text" name="nama_barang" required placeholder="Contoh: Laptop ASUS">
        </div>
        <div class="form-group">
            <label>Kategori</label>
            <input type="text" name="kategori" placeholder="Contoh: Elektronik">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="baik">Baik</option>
                <option value="rusak">Rusak</option>
            </select>
        </div>
        <div class="form-group">
            <label>Jumlah *</label>
            <input type="number" name="jumlah" required min="1" value="1">
        </div>
        <div class="form-group">
            <label>Lokasi</label>
            <input type="text" name="lokasi" placeholder="Contoh: Ruang ICT">
        </div>
        <button type="submit" name="tambah" class="btn btn-primary">➕ Tambah Barang</button>
    </form>
</div>

<h3>📋 Daftar Barang</h3>
<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Status</th><th>Stok</th><th>Lokasi</th><th>Aksi</th></tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($barang)): ?>
            <tr>
                <td><?= $row['kode_barang'] ?></td>
                <td><?= $row['nama_barang'] ?></td>
                <td><?= $row['kategori'] ?></td>
                <td class="status-<?= $row['status'] ?>"><?= $row['status'] ?></td>
                <td><strong><?= $row['jumlah'] ?></strong></td>
                <td><?= $row['lokasi'] ?></td>
                <td>
                    <a href="?page=tambah_barang&hapus=<?= $row['id_barang'] ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus?')">🗑️ Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>