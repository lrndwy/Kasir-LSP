<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kasir Restoran</title>
    <link href="/kasir_restoran/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="/kasir_restoran/assets/css/tailwind.css"> -->
    <link rel="stylesheet" href="/kasir_restoran/assets/css/all.css">
    <link rel="stylesheet" href="/kasir_restoran/assets/css/all.min.css">
    <link rel="stylesheet" href="/kasir_restoran/assets/css/sweetalert2.css">
    <link rel="stylesheet" href="/kasir_restoran/assets/css/sweetalert2.min.css">

    <?php 
    // Deteksi halaman aktif
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>

    <style>
        .form-select {
            display: block;
            width: 100%;
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            appearance: none;
        }
        
        .form-select:focus {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Styling untuk sidebar */
        .sidebar {
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            min-width: 280px;
            max-width: 280px;
        }

        /* Tambahan untuk mengatur konten utama */
        .main-content {
            margin-left: 280px;
            padding: 20px;
            width: calc(100% - 280px);
        }

        @media (max-width: 768px) {
            .sidebar {
                min-width: 250px;
                max-width: 250px;
            }
            .main-content {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
        }

        .sidebar .nav-link {
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar h4 {
            padding: 15px 0;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .sidebar hr {
            background-color: rgba(255,255,255,0.1);
            margin: 15px 0;
        }

        .sidebar .nav-item:last-child {
            margin-top: 20px;
        }

        .sidebar .nav-item:last-child .nav-link {
            border: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar .nav-item:last-child .nav-link:hover {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        /* Styling untuk menu aktif */
        .sidebar .nav-link.active {
            background-color: #0d6efd;
            color: white !important;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active i {
            color: white;
        }
    </style>
</head>
<body>
