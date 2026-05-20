<?php
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