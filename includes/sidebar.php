<?php
$role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar bg-dark text-white" style="min-height: 100vh; width: 280px;">
    <div class="p-3">
        <h4 class="text-center">Kasir Restoran</h4>
        <hr>
        <ul class="nav flex-column">
            <?php if($role == 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="/kasir_restoran/admin/index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" href="/kasir_restoran/admin/users.php">
                        <i class="fas fa-users"></i> Manajemen Pengguna
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'tables.php' ? 'active' : ''; ?>" href="/kasir_restoran/admin/tables.php">
                        <i class="fas fa-chair"></i> Manajemen Meja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo $current_page == 'menu.php' ? 'active' : ''; ?>" href="/kasir_restoran/admin/menu.php">
                        <i class="fas fa-utensils"></i> Manajemen Menu
                    </a>
                </li>
            <?php elseif($role == 'waiter'): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/kasir_restoran/waiter/index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/kasir_restoran/waiter/tables.php">
                        <i class="fas fa-chair"></i> Status Meja
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/kasir_restoran/waiter/orders.php">
                        <i class="fas fa-clipboard-list"></i> Pesanan
                    </a>
                </li>
            <?php elseif($role == 'kasir'): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/kasir_restoran/kasir/index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/kasir_restoran/kasir/orders.php">
                        <i class="fas fa-receipt"></i> Daftar Pesanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/kasir_restoran/kasir/transactions.php">
                        <i class="fas fa-cash-register"></i> Transaksi
                    </a>
                </li>
            <?php elseif($role == 'owner'): ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/kasir_restoran/owner/index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/kasir_restoran/owner/reports.php">
                        <i class="fas fa-chart-bar"></i> Laporan
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link text-white" href="javascript:void(0);" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<script>
function confirmLogout() {
    showConfirm(
        'Konfirmasi Logout',
        'Apakah Anda yakin ingin keluar dari sistem?',
        function() {
            window.location.href = '/kasir_restoran/auth/logout.php';
        }
    );
}
</script>
