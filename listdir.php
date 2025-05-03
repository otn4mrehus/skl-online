<?php
function listOnlyDirectories($dir, $base = '') {
    $items = scandir($dir);
    echo "<ul>";
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        $relativePath = ltrim($base . '/' . $item, '/'); // Hapus ./ dari tampilan
        if (is_dir($fullPath)) {
            echo "<li><strong>$relativePath</strong>";
            listOnlyDirectories($fullPath, $relativePath); // Rekursif
            echo "</li>";
        }
    }
    echo "</ul>";
}

$startDir = '.';
echo "<h2>Daftar Direktori:</h2>";
listOnlyDirectories($startDir);





/*
function listDirectory($dir) {
    $files = scandir($dir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($fullPath)) {
            echo "<li><strong>[DIR] $file</strong>";
            listDirectory($fullPath); // Panggil rekursif
            echo "</li>";
        } else {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
}

// Jalankan dari direktori sekarang atau sesuaikan
$startDir = '.';
echo "<h2>Isi Direktori: $startDir</h2>";
listDirectory($startDir);
*/


/*
$folder = './'; // Ganti dengan direktori yang ingin dibaca
$files = scandir($folder);

echo "<h2>Daftar File di $folder:</h2>";
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";
*/
?>
