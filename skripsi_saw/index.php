<!DOCTYPE html> 
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SPK Kinerja Kurir - Metode SAW</title>
    <style>
        /* Reset & base */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        header, .results {
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .rank-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            color: black;
            font-weight: bold;
        }
        .gold { background: gold; color: black; }
        .silver { background: silver; color: black; }
        .bronze { background: #cd7f32; color: white; }
    </style>
</head>
<body>
    <header>
        <h1>IMPLEMENTASI SISTEM PENDUKUNG KEPUTUSAN KINERJA KURIR</h1>
        <p>Menggunakan Metode Simple Additive Weighting (SAW)</p>
    </header>
    <main>
        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kurir</th>
                        <th>Kehadiran</th>
                        <th>Ketepatan Waktu</th>
                        <th>Jumlah Box</th>
                        <th>Kedisiplinan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="courierRows">
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="nama[]" required></td>
                        <td><input type="number" name="kehadiran[]" min="1" max="30" required></td>
                        <td><input type="time" name="ketepatan_waktu[]" required></td>
                        <td><input type="number" name="jumlah_box[]" max="660" required></td>
                        <td><input type="number" name="kedisiplinan[]" min="1" max="5" required></td>
                        <td><button type="button" class="removeBtn">✕</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" id="addRowBtn">+ Tambah Kurir</button>
            <button type="submit" name="submit">Hitung Penilaian</button>
        </form>

<?php
if (isset($_POST['submit'])) {
    $nama = $_POST['nama'];
    $kehadiran = $_POST['kehadiran'];
    $ketepatan_waktu = $_POST['ketepatan_waktu'];
    $jumlah_box = $_POST['jumlah_box'];
    $kedisiplinan = $_POST['kedisiplinan'];

    // Validasi input maksimum berdasarkan aturan
    for ($i = 0; $i < count($nama); $i++) {
        // Kehadiran: max 30
        if ($kehadiran[$i] > 30) $kehadiran[$i] = 30;

        // Konversi waktu (format: HH:MM) menjadi total menit
        $time = explode(':', $_POST['ketepatan_waktu'][$i]);
        $jam_menit = intval($time[0]) * 60 + intval($time[1]);

        // Konversi menit menjadi skor sesuai tabel
        if ($jam_menit <= 420) $ketepatan_waktu[$i] = 100;            // ≤ 07:00
        elseif ($jam_menit <= 450) $ketepatan_waktu[$i] = 90;         // 07:01–07:30
        elseif ($jam_menit <= 480) $ketepatan_waktu[$i] = 85;         // 07:31–08:00
        elseif ($jam_menit <= 510) $ketepatan_waktu[$i] = 80;         // 08:01–08:30
        elseif ($jam_menit <= 540) $ketepatan_waktu[$i] = 75;         // 08:31–09:00
        else $ketepatan_waktu[$i] = 60;                               // > 09:00

        // Jumlah box: max 660
        if ($jumlah_box[$i] > 660) $jumlah_box[$i] = 660;

        // Kedisiplinan: max 5, min 1
        if ($kedisiplinan[$i] > 5) $kedisiplinan[$i] = 5;
        if ($kedisiplinan[$i] < 1) $kedisiplinan[$i] = 1;
    }

    // Bobot tiap kriteria
    $bobot = [
        'kehadiran' => 0.30,
        'ketepatan_waktu' => 0.25,
        'jumlah_box' => 0.25,
        'kedisiplinan' => 0.20
    ];

    $nilai = [
        'kehadiran' => array_map('floatval', $kehadiran),
        'ketepatan_waktu' => array_map('floatval', $ketepatan_waktu),
        'jumlah_box' => array_map('floatval', $jumlah_box),
        'kedisiplinan' => array_map('floatval', $kedisiplinan)
    ];

    $normalisasi = [];
    foreach ($nilai as $kriteria => $vals) {
    if ($kriteria == 'jumlah_box') {
        $normalisasi[$kriteria] = array_map(fn($v) => $v / 660, $vals);
    } else {
        $max = max($vals);
        $normalisasi[$kriteria] = array_map(fn($v) => $v / ($max ?: 1), $vals);
    }
}

    $scores = [];
    $perkalian = [];
    for ($i = 0; $i < count($nama); $i++) {
        $total = 0;
        foreach ($bobot as $k => $b) {
            $perkalian[$i][$k] = $normalisasi[$k][$i] * $b;
            $total += $perkalian[$i][$k];
        }
        $scores[$i] = $total;
    }

    arsort($scores);
    echo '<div style="text-align: center; margin-top: 30px;">
            <button onclick="downloadPDF()">📄 Download Halaman Sebagai PDF</button>
            </div>';
    echo '<div class="results" id="printArea">';
    echo '<h2>Hasil Penilaian Kinerja Kurir</h2>';
    echo '<table><thead><tr><th>Rank</th><th>Nama</th><th>Skor</th></tr></thead><tbody>';
    $rank = 1;
    foreach ($scores as $i => $score) {
        $class = $rank == 1 ? 'gold' : ($rank == 2 ? 'silver' : ($rank == 3 ? 'bronze' : ''));
        echo "<tr><td><span class='rank-badge $class'>#{$rank}</span></td><td>{$nama[$i]}</td><td>".number_format($score, 4)."</td></tr>";
        $rank++;
    }
echo '</tbody></table>';
    // Tampilkan Normalisasi Matriks Keputusan
    echo '<h3>Normalisasi Matriks Keputusan</h3>';
    echo '<table><thead><tr><th>Nama</th>';
    foreach ($bobot as $k => $_) echo "<th>$k</th>";
    echo '</tr></thead><tbody>';
    for ($i = 0; $i < count($nama); $i++) {
    echo "<tr><td>{$nama[$i]}</td>";
    foreach ($bobot as $k => $_) {
        echo "<td>{$normalisasi[$k][$i]}</td>";
    }
    echo '</tr>';
}
echo '</tbody></table>';

    echo '<h3>Hasil Perkalian Normalisasi dengan Bobot</h3>';
    echo '<table><thead><tr><th>Nama</th>';
    foreach ($bobot as $k => $_) echo "<th>$k</th>";
    echo '</tr></thead><tbody>';
    foreach ($scores as $i => $_) {
        echo "<tr><td>{$nama[$i]}</td>";
        foreach ($bobot as $k => $_) echo "<td>".number_format($perkalian[$i][$k], 4)."</td>";
        echo '</tr>';
    }
echo '</tbody></table>';

echo '</tbody></table>';
    // Saran per kurir berdasarkan nilai normalisasi
    echo '<h3 style="text-align: center;">Saran Peningkatan Kinerja Kurir</h3>';
    echo '<table><thead><tr><th>Nama</th><th>Saran Perbaikan</th></tr></thead><tbody>';
    foreach ($scores as $i => $_) {
        $saran = [];
        foreach ($bobot as $kriteria => $_) {
        if ($normalisasi[$kriteria][$i] < 0.7) {
            $saran[] = ucfirst(str_replace('_', ' ', $kriteria));
            }
        }

    if (count($saran) > 0) {
        echo "<tr><td>{$nama[$i]}</td><td>Perlu meningkatkan aspek: <strong>" . implode(', ', $saran) . "</strong></td></tr>";
    } else {
        echo "<tr><td>{$nama[$i]}</td><td><em>Tidak ada saran khusus, semua kriteria sudah cukup baik dan tolong dipertahankan</em></td></tr>";
    }
}
echo '</tbody></table></div>'; // tutup printArea
}
?>
    </main>
    <script>
        const courierRows = document.getElementById('courierRows');
        document.getElementById('addRowBtn').onclick = () => {
            const row = document.createElement('tr');
            row.innerHTML = courierRows.children[0].innerHTML;
            courierRows.appendChild(row);
            updateNumbers();
            row.querySelector('.removeBtn').onclick = () => row.remove();
        };
        function updateNumbers() {
            courierRows.querySelectorAll('tr').forEach((row, i) => {
                row.children[0].textContent = i + 1;
            });
        }
    </script>
    
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const element = document.getElementById('printArea');
        const opt = {
            margin:       0.5,
            filename:     'laporan_penilaian_kurir.pdf',
            image:        { type: 'jpeg', quality: 1.00 },
            html2canvas:  { scale: 3 },
            jsPDF:        { unit: 'in', format: 'legal', orientation: 'portrait' },
            pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>

</body>
</html>
