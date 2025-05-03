<?php
// Mulai session
session_start();

// Konfigurasi dasar
$school_name = "SMKN 6 Kota Serang";
$school_year = "2024/2025";

// File paths
$students_file = 'siswa.csv';
$settings_file = 'settings.csv';
$upload_dir = 'upload/';
$photo_prefix = 'foto_';
$skl_prefix = 'skl_';

// Daftar kelas yang tersedia
$classes = [
    'XIITKJ-1',
    'XIITKJ-2',
    'XIITKJ-3',
    'XIIAKL-1',
    'XIIAKL-2',
    'XIIAKL-3',
    'XIITPL-1',
    'XIITSM-1'
];

// Inisialisasi variabel
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$error = '';
$success = '';

// Fungsi untuk memuat settings dari CSV
function loadSettings($file) {
    $settings = [
        'announcement_time' => date('Y-m-d H:i:s', strtotime('+0 days')),
        'admin_username' => 'admin',
        'admin_password' => 'admin123'
    ];
    
    if (file_exists($file)) {
        $handle = fopen($file, 'r');
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 2) {
                $settings[$data[0]] = $data[1];
            }
        }
        fclose($handle);
    }
    return $settings;
}

// Fungsi untuk menyimpan settings ke CSV
function saveSettings($file, $settings) {
    $handle = fopen($file, 'w');
    foreach ($settings as $key => $value) {
        fputcsv($handle, [$key, $value]);
    }
    fclose($handle);
}

// Memuat settings
$settings = loadSettings($settings_file);
$default_announcement_time = $settings['announcement_time'];

// Fungsi untuk memuat data siswa dari CSV dengan validasi
function loadStudents($file) {
    $students = [];
    if (file_exists($file)) {
        $handle = fopen($file, 'r');
        
        // Lewati header jika ada
        $header = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            // Pastikan data memiliki 8 kolom
            if (count($data) < 8) {
                continue;
            }
            
            $students[$data[1]] = [
                'name' => $data[0] ?? '',
                'nisn' => $data[1] ?? '',
                'birth_place' => $data[2] ?? '',
                'birth_date' => $data[3] ?? '',
                'class' => $data[4] ?? '',
                'status' => $data[5] ?? 'TIDAK LULUS',
                'photo' => $data[6] ?? '',
                'skl' => $data[7] ?? ''
            ];
        }
        fclose($handle);
    }
    return $students;
}

// Fungsi untuk menyimpan data siswa ke CSV dengan header
function saveStudents($file, $students) {
    $handle = fopen($file, 'w');
    
    // Tulis header
    fputcsv($handle, ['name', 'nisn', 'birth_place', 'birth_date', 'class', 'status', 'photo', 'skl']);
    
    foreach ($students as $student) {
        fputcsv($handle, [
            $student['name'],
            $student['nisn'],
            $student['birth_place'],
            $student['birth_date'],
            $student['class'],
            $student['status'],
            $student['photo'],
            $student['skl']
        ]);
    }
    fclose($handle);
}

// Memuat data siswa
$students = loadStudents($students_file);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?page=admin_login');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_student'])) {
        $nisn = trim($_POST['nisn']);
        $student_result = checkStudent($nisn, $students, $default_announcement_time);
    } 
    elseif (isset($_POST['admin_login'])) {
        $username = trim($_POST['admin_username']);
        $password = trim($_POST['admin_password']);
        
        if ($username === $settings['admin_username'] && $password === $settings['admin_password']) {
            $_SESSION['admin_logged_in'] = true;
            $page = 'admin_dashboard';
        } else {
            $error = 'Username atau password salah!';
            $page = 'admin_login';
        }
    }
    elseif (isset($_POST['upload_student']) && isset($_SESSION['admin_logged_in'])) {
        // Handle upload data siswa
        $name = trim($_POST['student_name']);
        $nisn = trim($_POST['student_nisn']);
        $birth_place = trim($_POST['student_birth_place']);
        $birth_date = trim($_POST['student_birth_date']);
        $class = trim($_POST['student_class']);
        $status = isset($_POST['student_status']) ? 'LULUS' : 'TIDAK LULUS';
        
        // Proses upload foto
        $photo_filename = '';
        if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
            $photo_ext = pathinfo($_FILES['student_photo']['name'], PATHINFO_EXTENSION);
            $photo_filename = $photo_prefix . $nisn . '.' . $photo_ext;
            $photo_path = $upload_dir . $photo_filename;
            
            if (!move_uploaded_file($_FILES['student_photo']['tmp_name'], $photo_path)) {
                $error = 'Gagal mengupload foto siswa';
            }
        }
        
        // Proses upload SKL
        $skl_filename = '';
        if (isset($_FILES['skl_file']) && $_FILES['skl_file']['error'] === UPLOAD_ERR_OK) {
            $skl_ext = pathinfo($_FILES['skl_file']['name'], PATHINFO_EXTENSION);
            $skl_filename = $skl_prefix . $nisn . '.' . $skl_ext;
            $skl_path = $upload_dir . $skl_filename;
            
            if (!move_uploaded_file($_FILES['skl_file']['tmp_name'], $skl_path)) {
                $error = 'Gagal mengupload file SKL';
            }
        }
        
        if (empty($error)) {
            // Tambahkan/update data siswa
            $students[$nisn] = [
                'name' => $name,
                'nisn' => $nisn,
                'birth_place' => $birth_place,
                'birth_date' => $birth_date,
                'class' => $class,
                'status' => $status,
                'photo' => $photo_filename,
                'skl' => $skl_filename
            ];
            
            // Simpan ke CSV
            saveStudents($students_file, $students);
            $success = 'Data siswa berhasil disimpan!';
        }
    }
    elseif (isset($_POST['save_settings']) && isset($_SESSION['admin_logged_in'])) {
        // Handle pengaturan
        if (!empty($_POST['announcement_time'])) {
            $settings['announcement_time'] = $_POST['announcement_time'];
            $default_announcement_time = $settings['announcement_time'];
            $success = 'Pengaturan berhasil disimpan!';
        }
        
        // Handle perubahan password admin
        if (!empty($_POST['admin_password_change'])) {
            $settings['admin_password'] = $_POST['admin_password_change'];
            $success = 'Pengaturan berhasil disimpan!';
        }
        
        // Simpan settings ke file
        saveSettings($settings_file, $settings);
    }
    elseif (isset($_POST['delete_student']) && isset($_SESSION['admin_logged_in'])) {
        // Handle penghapusan siswa
        $nisn_to_delete = $_POST['nisn_to_delete'];
        if (isset($students[$nisn_to_delete])) {
            // Hapus file foto dan SKL
            if (!empty($students[$nisn_to_delete]['photo'])) {
                @unlink($upload_dir . $students[$nisn_to_delete]['photo']);
            }
            if (!empty($students[$nisn_to_delete]['skl'])) {
                @unlink($upload_dir . $students[$nisn_to_delete]['skl']);
            }
            
            // Hapus dari array
            unset($students[$nisn_to_delete]);
            
            // Simpan ke CSV
            saveStudents($students_file, $students);
            $success = 'Data siswa berhasil dihapus!';
        }
    }
}

// Redirect ke halaman admin jika sudah login
if (isset($_SESSION['admin_logged_in']) && ($page === 'admin_login' || $page === 'home')) {
    $page = 'admin_dashboard';
}

// Fungsi untuk memeriksa siswa
function checkStudent($nisn, $students, $announcement_time) {
    $now = new DateTime();
    $announcement = new DateTime($announcement_time);
    
    if ($now < $announcement) {
        return [
            'status' => 'warning',
            'message' => 'PENGUMUMAN BELUM DIBUKA',
            'detail' => 'Pengumuman kelulusan akan dibuka pada:<br><strong>' . formatDate($announcement) . '</strong>'
        ];
    }
    
    if (isset($students[$nisn])) {
        $student = $students[$nisn];
        return [
            'status' => $student['status'] === 'LULUS' ? 'success' : 'danger',
            'message' => $student['status'] === 'LULUS' ? 'SELAMAT! ANDA LULUS' : 'ANDA TIDAK LULUS',
            'detail' => $student,
            'show_download' => $student['status'] === 'LULUS'
        ];
    } else {
        return [
            'status' => 'danger',
            'message' => 'DATA TIDAK DITEMUKAN',
            'detail' => 'NISN yang Anda masukkan tidak terdaftar. Silakan coba lagi.'
        ];
    }
}

// Fungsi utilitas untuk format tanggal
function formatDate($date) {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format('l, d F Y H:i');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Kelulusan - <?php echo $school_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS tetap sama seperti sebelumnya */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            padding-top: 56px; /* Height of navbar */
            scroll-padding-top: 56px; /* For anchor links */
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 0;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            border: none;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .hidden {
            display: none;
        }
        
        #studentResult {
            transition: all 0.5s ease;
        }
        
        footer {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        /* Adjust hero section to account for fixed navbar */
        .hero-section {
            margin-top: 56px;
            border-radius: 0 !important;
        }

        /* Mobile menu adjustments */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background-color: #343a40;
                padding: 10px;
                margin-top: 8px;
                border-radius: 5px;
                max-height: calc(100vh - 56px);
                overflow-y: auto;
            }
        }

        .nav-tabs .nav-link {
            color: var(--primary-color);
        }
        
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid var(--secondary-color);
        }
        
        .preview-image {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
        }
        
        .birth-date-container {
            display: flex;
            gap: 10px;
        }
        
        .birth-date-container .form-control {
            flex: 1;
        }
    </style>
</head>
<body>
    <!-- Header/Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="?page=home">
                <img src="assets/images/logo.png" alt="<?php echo $school_name; ?>" height="40">
                <span class="ms-2"><?php echo $school_name; ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'home' ? 'active' : ''; ?>" href="?page=home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'check' ? 'active' : ''; ?>" href="?page=check">Cek Kelulusan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'about' ? 'active' : ''; ?>" href="?page=about">Tentang</a>
                    </li>
                    <?php if (isset($_SESSION['admin_logged_in'])): ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-sm btn-outline-light ms-2 active" href="?page=admin_dashboard">Admin</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-sm btn-outline-danger ms-2" href="?logout=1">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-sm btn-outline-light ms-2 <?php echo $page === 'admin_login' ? 'active' : ''; ?>" 
                               href="?page=admin_login">Admin</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($page === 'home' || $page === 'check'): ?>
        <!-- Hero Section -->
        <section class="hero-section" style="margin-top: <?php echo isset($_SESSION['admin_logged_in']) ? '56px' : '0'; ?>">
            <div class="container text-center">
                <h1 class="display-4 fw-bold mb-4">PENGUMUMAN KELULUSAN</h1>
                <p class="lead mb-5"><?php echo $school_name; ?> Tahun Ajaran <?php echo $school_year; ?></p>
                
                <div class="countdown-container bg-white p-3 rounded d-inline-block">
                    <p class="mb-2 text-dark">Pengumuman akan dibuka dalam:</p>
                    <div class="countdown" id="countdown">
                        <span id="days">00</span> Hari 
                        <span id="hours">00</span>:<span id="minutes">00</span>:<span id="seconds">00</span>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container my-5">
        <?php if ($page === 'home'): ?>
            <!-- Home Page Content -->
            <div class="row">
                <div class="col-md-8 mx-auto text-center">
                    <h2 class="mb-4">Selamat Datang di Portal Pengumuman Kelulusan</h2>
                    <p class="lead">Portal ini menyediakan informasi mengenai kelulusan siswa <?php echo $school_name; ?> Tahun Ajaran <?php echo $school_year; ?>.</p>
                    
                    <div class="card mt-5">
                        <div class="card-body">
                            <h3 class="card-title">Cara Mengecek Kelulusan</h3>
                            <ol class="text-start">
                                <li>Klik menu "Cek Kelulusan" atau tombol di bawah ini</li>
                                <li>Masukkan NISN Anda pada form yang tersedia</li>
                                <li>Klik tombol "Cek Kelulusan"</li>
                                <li>Hasil akan ditampilkan secara otomatis</li>
                                <li>Jika lulus, Anda dapat mengunduh surat kelulusan</li>
                            </ol>
                            
                            <a href="?page=check" class="btn btn-primary btn-lg mt-3">
                                <i class="fas fa-search me-2"></i>Cek Kelulusan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($page === 'check'): ?>
            <!-- Student Check Section -->
            <section class="mb-5">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body p-5">
                                <h3 class="card-title text-center mb-4">CEK STATUS KELULUSAN</h3>
                                <form method="POST" action="?page=check">
                                    <div class="mb-3">
                                        <label for="nisn" class="form-label">Masukkan NISN Anda</label>
                                        <input type="text" class="form-control form-control-lg" id="nisn" name="nisn"
                                               placeholder="Contoh: 1234567890" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="check_student" class="btn btn-primary btn-lg">
                                            <i class="fas fa-search me-2"></i>Cek Kelulusan
                                        </button>
                                    </div>
                                </form>
                                
                                <?php if (isset($student_result)): ?>
                                    <div id="studentResult" class="mt-4">
                                        <div class="text-center">
                                            <?php if (isset($student_result['detail']['photo']) && !empty($student_result['detail']['photo'])): ?>
                                                <img src="<?php echo $upload_dir . $student_result['detail']['photo']; ?>" 
                                                     alt="Foto Siswa" class="rounded-circle mb-3" width="120">
                                            <?php endif; ?>
                                            
                                            <?php if (isset($student_result['detail']['name'])): ?>
                                                <h4><?php echo $student_result['detail']['name']; ?></h4>
                                                <p class="text-muted">
                                                    <?php echo $student_result['detail']['birth_place']; ?>, 
                                                    <?php echo date('d F Y', strtotime($student_result['detail']['birth_date'])); ?> | 
                                                    <?php echo $student_result['detail']['class']; ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="alert alert-<?php echo $student_result['status']; ?>">
                                                <h5><?php echo $student_result['message']; ?></h5>
                                                <?php if (isset($student_result['detail']) && is_string($student_result['detail'])): ?>
                                                    <p><?php echo $student_result['detail']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (isset($student_result['show_download']) && $student_result['show_download']): ?>
                                                <a href="<?php echo $upload_dir . $student_result['detail']['skl']; ?>" 
                                                   class="btn btn-success mt-3" download>
                                                    <i class="fas fa-download me-2"></i>Download Surat Kelulusan
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
        <?php elseif ($page === 'about'): ?>
            <!-- About Section -->
            <section>
                <div class="row">
                    <div class="col-md-6">
                        <h2>Tentang <?php echo $school_name; ?></h2>
                        <p><?php echo $school_name; ?> merupakan salah satu sekolah menengah kejuruan terbaik di Kota Serang yang mencetak lulusan siap kerja dan berkompeten di bidangnya.</p>
                        
                        <h3 class="mt-4">Visi</h3>
                        <p>Menjadi sekolah kejuruan unggulan yang menghasilkan lulusan berkompeten, berkarakter, dan siap bersaing di dunia kerja.</p>
                        
                        <h3 class="mt-4">Misi</h3>
                        <ul>
                            <li>Menyelenggarakan pendidikan kejuruan yang berkualitas</li>
                            <li>Mengembangkan kompetensi siswa sesuai kebutuhan industri</li>
                            <li>Membentuk karakter siswa yang berakhlak mulia</li>
                            <li>Membangun kerjasama dengan dunia usaha dan industri</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h3>Kontak</h3>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-map-marker-alt me-2"></i> LINK. PRIYAYI LANGGAR NO. 69, Kel. Mesjid Priyayi, Kec. Kasemen, Kota Serang, Prov. Banten - 42191</li>
                            <li><i class="fas fa-phone me-2"></i> (0254) 2576575</li>
                            <li><i class="fas fa-envelope me-2"></i> info@smkn6kotaserang.sch.id / smkn6kotaserang@gmail.com</li>
                            <li><i class="fas fa-clock me-2"></i> Senin-Jumat: 07:00 - 16:00</li>
                        </ul>
                        
                        <h3 class="mt-4">Program Keahlian</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Teknik Jaringan Komputer dan Telekomunikasi</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Akuntansi Keuangan dan Lembaga</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Teknik dan Bisnis Sepeda Motor</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Teknik Pengelasan dan Fabrikasi Logam </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
        <?php elseif ($page === 'admin_login'): ?>
            <!-- Admin Login Section -->
            <section>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body p-5">
                                <h3 class="card-title text-center mb-4">LOGIN ADMIN</h3>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST" action="?page=admin_login">
                                    <div class="mb-3">
                                        <label for="admin_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="admin_password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="admin_login" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
        <?php elseif ($page === 'admin_dashboard' && isset($_SESSION['admin_logged_in'])): ?>
            <!-- Admin Dashboard Section -->
            <section>
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Dashboard Admin</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <ul class="nav nav-tabs" id="adminTabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#uploadTab">Upload Data</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#settingsTab">Pengaturan</a>
                            </li>
                        </ul>
                        
                        <div class="tab-content mt-3">
                            <!-- Upload Tab -->
                            <div class="tab-pane fade show active" id="uploadTab">
                                <form id="uploadForm" enctype="multipart/form-data" method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="studentName" class="form-label">Nama Siswa</label>
                                            <input type="text" class="form-control" id="studentName" name="student_name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="studentNISN" class="form-label">NISN</label>
                                            <input type="text" class="form-control" id="studentNISN" name="student_nisn" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="studentBirthPlace" class="form-label">Tempat Lahir</label>
                                            <input type="text" class="form-control" id="studentBirthPlace" name="student_birth_place" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="studentBirthDate" class="form-label">Tanggal Lahir</label>
                                            <input type="date" class="form-control" id="studentBirthDate" name="student_birth_date" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="studentClass" class="form-label">Kelas</label>
                                        <select class="form-select" id="studentClass" name="student_class" required>
                                            <option value="">Pilih Kelas</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?php echo htmlspecialchars($class); ?>"><?php echo htmlspecialchars($class); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="studentPhotoFile" class="form-label">Foto Siswa</label>
                                        <input type="file" class="form-control" id="studentPhotoFile" name="student_photo" accept="image/*">
                                        <small class="text-muted">Format: <?php echo $photo_prefix; ?>[nisn].[ext] (contoh: <?php echo $photo_prefix; ?>1234567890.png)</small>
                                        <div id="photoPreview" class="preview-image"></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sklFile" class="form-label">File Surat Kelulusan (PDF)</label>
                                        <input type="file" class="form-control" id="sklFile" name="skl_file" accept=".pdf">
                                        <small class="text-muted">Format: <?php echo $skl_prefix; ?>[nisn].pdf (contoh: <?php echo $skl_prefix; ?>1234567890.pdf)</small>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="studentStatus" name="student_status" checked>
                                        <label class="form-check-label" for="studentStatus">Status Lulus</label>
                                    </div>
                                    
                                    <button type="submit" name="upload_student" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Upload Data
                                    </button>
                                </form>
                                
                                <hr class="my-4">
                                
                                <h5>Daftar Siswa Terupload</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>NISN</th>
                                                <th>Nama</th>
                                                <th>Kelas</th>
                                                <th>Status</th>
                                                <th>Foto</th>
                                                <th>SKL</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $nisn => $student): ?>
                                                <tr>
                                                    <td><?php echo $student['nisn']; ?></td>
                                                    <td><?php echo $student['name']; ?></td>
                                                    <td><?php echo $student['class']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $student['status'] === 'LULUS' ? 'success' : 'danger'; ?>">
                                                            <?php echo $student['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($student['photo'])): ?>
                                                            <img src="<?php echo $upload_dir . $student['photo']; ?>" alt="Foto" width="50">
                                                        <?php else: ?>
                                                            <span class="text-muted">Tidak ada</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($student['skl'])): ?>
                                                            <a href="<?php echo $upload_dir . $student['skl']; ?>" target="_blank">
                                                                <i class="fas fa-file-pdf"></i> Lihat
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">Tidak ada</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="nisn_to_delete" value="<?php echo $student['nisn']; ?>">
                                                            <button type="submit" name="delete_student" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Settings Tab -->
                            <div class="tab-pane fade" id="settingsTab">
                                <form id="settingsForm" method="POST">
                                    <div class="mb-3">
                                        <label for="announcementTime" class="form-label">Waktu Pengumuman Kelulusan</label>
                                        <input type="datetime-local" class="form-control" id="announcementTime" 
                                               name="announcement_time" value="<?php echo date('Y-m-d\TH:i', strtotime($default_announcement_time)); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="adminUsernameChange" class="form-label">Username Admin</label>
                                        <input type="text" class="form-control" id="adminUsernameChange" name="admin_username_change" 
                                               value="<?php echo htmlspecialchars($settings['admin_username']); ?>" readonly>
                                        <small class="text-muted">Username tidak dapat diubah</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="adminPasswordChange" class="form-label">Password Baru</label>
                                        <input type="password" class="form-control" id="adminPasswordChange" name="admin_password_change">
                                        <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                    </div>
                                    
                                    <button type="submit" name="save_settings" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Simpan Pengaturan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> ICT Center - <?php echo $school_name; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript untuk countdown
// Handle mobile menu scrolling
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar');
    const navbarHeight = navbar.offsetHeight;
    
    // Update body padding based on navbar height
    document.body.style.paddingTop = navbarHeight + 'px';
    
    // Adjust when menu is toggled on mobile
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    navbarToggler.addEventListener('click', function() {
        if (navbarCollapse.classList.contains('show')) {
            document.body.style.paddingTop = navbarHeight + 'px';
        } else {
            const expandedHeight = navbarHeight + navbarCollapse.scrollHeight;
            document.body.style.paddingTop = expandedHeight + 'px';
        }
    });
    
    // Recalculate on window resize
    window.addEventListener('resize', function() {
        document.body.style.paddingTop = document.querySelector('.navbar').offsetHeight + 'px';
    });
});


        document.addEventListener('DOMContentLoaded', function() {
            // Set waktu pengumuman (dalam implementasi nyata, ambil dari database)
            const announcementTime = new Date("<?php echo $default_announcement_time; ?>");
            
            // Fungsi untuk menghitung mundur
            function updateCountdown() {
                const now = new Date();
                const diff = announcementTime - now;
                
                if (diff <= 0) {
                    document.getElementById('countdown').innerHTML = 'PENGUMUMAN TELAH DIBUKA!';
                    return;
                }
                
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                
                document.getElementById('days').textContent = days.toString().padStart(2, '0');
                document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            }
            
            // Jalankan countdown setiap detik
            setInterval(updateCountdown, 1000);
            updateCountdown(); // Panggil sekali saat pertama kali load
            
            // Inisialisasi tab Bootstrap
            const tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabElms.forEach(tabEl => {
                tabEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    const tab = new bootstrap.Tab(this);
                    tab.show();
                });
            });
            
            // Preview gambar sebelum upload
            document.getElementById('studentPhotoFile').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('photoPreview').innerHTML = 
                            `<img src="${e.target.result}" class="img-thumbnail" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>
