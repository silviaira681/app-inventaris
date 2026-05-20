<?php
$where = "";
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';

if ($tgl_awal && $tgl_akhir) {
    $where = "WHERE p.tanggal_pinjam BETWEEN '$tgl_awal' AND '$tgl_akhir'";
}

$laporan = mysqli_query($conn, "SELECT p.*, b.kode_barang, b.nama_barang, peng.kondisi_barang, peng.tanggal_kembali as tgl_kembali 
    FROM peminjaman p 
    JOIN barang b ON p.id_barang = b.id_barang 
    LEFT JOIN pengembalian peng ON p.id_peminjaman = peng.id_peminjaman 
    $where 
    ORDER BY p.tanggal_pinjam DESC");
?>

<h2>📄 Laporan Peminjaman Barang</h2>

<div class="card no-print">
    <h3>Filter Laporan</h3>
    <form method="GET">
        <input type="hidden" name="page" value="laporan">
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1;">
                <label>Dari Tanggal</label>
                <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Sampai Tanggal</label>
                <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-info" onclick="window.print()">🖨️ Cetak Laporan</button>
            </div>
        </div>
    </form>
</div>

<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Peminjam</th>
                <th>Jumlah</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
                <th>Kondisi Kembali</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($laporan) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($laporan)): ?>
                <tr>
                    <td><?= $row['id_peminjaman'] ?></td>
                    <td><?= $row['kode_barang'] ?></td>
                    <td><?= $row['nama_barang'] ?></td>
                    <td><?= $row['peminjam'] ?></td>
                    <td><?= $row['jumlah_pinjam'] ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                    <td><?= $row['tgl_kembali'] ? date('d/m/Y', strtotime($row['tgl_kembali'])) : '-' ?></td>
                    <td><?= $row['status_peminjaman'] ?></td>
                    <td class="status-<?= $row['kondisi_barang'] ?>"><?= $row['kondisi_barang'] ?? '-' ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center;">Belum ada data peminjaman</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($tgl_awal && $tgl_akhir): ?>
<div class="card no-print" style="margin-top: 20px;">
    <p><strong>Total Data:</strong> <?= mysqli_num_rows($laporan) ?> peminjaman</p>
    <p><strong>Periode:</strong> <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
</div>
<?php endif; ?>