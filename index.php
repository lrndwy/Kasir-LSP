<?php
session_start();
if(isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: /kasir_restoran/$role/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kasir Restoran</title>
    <link href="/kasir_restoran/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #f5f5f5;">
    <div class="container">
        <div class="row justify-content-center" style="margin-top: 100px;">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Login Sistem Kasir Restoran</h3>
                        <p>Silakan masuk ke akun Anda</p>
                    </div>
                    <div class="card-body">
                        <?php if(isset($_GET['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>
                        <form action="/kasir_restoran/auth/login.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo '/kasir_restoran/assets/js/bootstrap.bundle.min.js'; ?>"></script>
    <script src="<?php echo '/kasir_restoran/assets/js/jquery.js'; ?>"></script>
    <script src="<?php echo '/kasir_restoran/assets/js/all.min.js'; ?>"></script>
    <script src="<?php echo '/kasir_restoran/assets/js/all.js'; ?>"></script>
    <script src="<?php echo '/kasir_restoran/assets/js/sweetalert2.js'; ?>"></script>
    <script src="<?php echo '/kasir_restoran/assets/js/sweetalert2.min.js'; ?>"></script>
</body>
</html>
