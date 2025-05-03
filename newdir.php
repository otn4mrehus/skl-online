<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mendapatkan nama folder proyek dari form
    $projectName = $_POST['project_name'];
    
    // Direktori dasar tempat folder proyek akan dibuat
    $baseDir = '/opt/lampp/htdocs/';
    
    // Menambahkan nama folder proyek ke direktori dasar
    $directory = $baseDir . $projectName;
    
    // Menyimpan pesan untuk modal
    $modalMessage = '';
    $modalStatus = ''; // Menandakan status keberhasilan atau kegagalan

    // Mengecek apakah direktori sudah ada
    if (!is_dir($directory)) {
        // Jika direktori belum ada, maka buat direktori
        $createDirCommand = "sudo mkdir -p $directory";
        $outputCreateDir = shell_exec($createDirCommand);
        if ($outputCreateDir === null) {
            $modalMessage = "🎉 Direktori '$directory' berhasil dibuat!";
            $modalStatus = 'success'; // Status berhasil
        } else {
            $modalMessage = "❌ Gagal membuat direktori '$directory'. Output: $outputCreateDir";
            $modalStatus = 'error'; // Status gagal
        }
    } else {
        $modalMessage = "🚨 Direktori '$directory' sudah ada!";
        $modalStatus = 'warning'; // Status peringatan
    }

    // Mengubah izin dan kepemilikan direktori
    $chmodCommand = "sudo chmod -R 777 $directory";
    $outputChmod = shell_exec($chmodCommand);
    if ($outputChmod === null) {
        $modalMessage .= "\n✅ Izin untuk $directory telah diubah menjadi 777.";
    } else {
        $modalMessage .= "\n❌ Gagal mengubah izin. Output: $outputChmod";
    }

    $chownCommand = "sudo chown -R www-data:www-data $directory";
    $outputChown = shell_exec($chownCommand);
    if ($outputChown === null) {
        $modalMessage .= "\n✅ Kepemilikan untuk $directory telah diubah menjadi www-data.";
    } else {
        $modalMessage .= "\n❌ Gagal mengubah kepemilikan. Output: $outputChown";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pembuatan Direktori</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
    <script>
        // Fungsi untuk membuka modal
        function openModal(message, status) {
            let modal = document.getElementById("modal");
            let modalMessage = document.getElementById("modalMessage");
            let modalContainer = document.getElementById("modalContainer");

            modalMessage.innerText = message;
            modal.classList.remove("hidden");

            // Menambahkan kelas sesuai dengan status (success, error, warning)
            modalContainer.className = `bg-white p-6 rounded shadow-lg w-1/3 transition-all ${status === 'success' ? 'bg-green-100 border-l-4 border-green-500' : status === 'error' ? 'bg-red-100 border-l-4 border-red-500' : 'bg-yellow-100 border-l-4 border-yellow-500'}`;
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            document.getElementById("modal").classList.add("hidden");
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <!-- Form untuk input nama folder -->
        <form method="POST" action="" class="bg-white p-6 rounded shadow-lg">
            <div class="mb-4">
                <label for="project_name" class="block text-gray-700 font-semibold mb-2">Nama Folder Proyek</label>
                <input type="text" id="project_name" name="project_name" class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Buat Direktori</button>
        </form>

        <!-- Modal untuk informasi -->
        <div id="modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex justify-center items-center hidden">
            <div id="modalContainer" class="bg-white p-6 rounded shadow-lg w-1/3 transition-all">
                <h2 class="text-xl font-semibold mb-4">Informasi</h2>
                <div class="mb-4">
                    <p id="modalMessage"></p>
                </div>
                <button onclick="closeModal()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // Jika ada pesan di PHP, tampilkan modal dengan informasi
        <?php if (isset($modalMessage)) { ?>
            window.onload = function() {
                var modalMessage = '<?php echo addslashes($modalMessage); ?>';
                var modalStatus = '<?php echo $modalStatus; ?>';
                openModal(modalMessage, modalStatus);
            }
        <?php } ?>
    </script>
</body>
</html>
