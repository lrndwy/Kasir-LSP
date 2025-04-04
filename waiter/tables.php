<?php
session_start();
require_once '../includes/alert.php';
require_once '../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'waiter') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';

// Ambil daftar meja
$stmt = $pdo->query("SELECT * FROM tables ORDER BY table_number");
$tables = $stmt->fetchAll();

// Ambil daftar menu
$stmt = $pdo->query("SELECT * FROM menu ORDER BY category, name");
$menu_items = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <div id="alertPlaceholder" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 90%; width: fit-content;"></div>
        <h2>Status Meja</h2>
        
        <div class="row mt-4">
            <?php foreach ($tables as $table): ?>
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Meja <?php echo $table['table_number']; ?></h5>
                        <p class="card-text">
                            <span class="badge <?php echo $table['status'] == 'available' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $table['status'] == 'available' ? 'Tersedia' : 'Terisi'; ?>
                            </span>
                        </p>
                        <?php if ($table['status'] == 'available'): ?>
                        <button class="btn btn-primary" onclick="createOrder(<?php echo $table['id']; ?>)">
                            Buat Pesanan
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal Buat Pesanan -->
<div class="modal fade" id="createOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Pesanan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="orderForm">
                    <input type="hidden" id="table_id" name="table_id">
                    
                    <div class="mb-3">
                        <h6>Menu</h6>
                        <div id="menuItems">
                            <?php foreach ($menu_items as $item): ?>
                            <div class="row mb-2 align-items-center">
                                <div class="col">
                                    <label><?php echo $item['name']; ?></label>
                                    <br>
                                    <small class="text-muted">
                                        Rp <?php echo number_format($item['price'], 0, ',', '.'); ?>
                                    </small>
                                </div>
                                <div class="col-auto">
                                    <div class="input-group" style="width: 120px;">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="form-control form-control-sm text-center" 
                                               id="qty_<?php echo $item['id']; ?>" 
                                               name="items[<?php echo $item['id']; ?>]" 
                                               value="0" min="0" readonly>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitOrder()">Simpan Pesanan</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function createOrder(tableId) {
    document.getElementById('table_id').value = tableId;
    // Reset semua quantity ke 0
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.value = 0;
    });
    new bootstrap.Modal(document.getElementById('createOrderModal')).show();
    showAlert('info', 'Silakan pilih menu untuk pesanan baru');
}

function updateQuantity(menuId, change) {
    const input = document.getElementById(`qty_${menuId}`);
    let value = parseInt(input.value) + change;
    input.value = Math.max(0, value);
}

function submitOrder() {
    const formData = new FormData(document.getElementById('orderForm'));
    formData.append('waiter_id', <?php echo $_SESSION['user_id']; ?>);

    // Validasi pesanan
    let hasItems = false;
    const items = formData.getAll('items[]');
    
    // Periksa setiap input menu
    document.querySelectorAll('input[type="number"]').forEach(input => {
        if (parseInt(input.value) > 0) {
            hasItems = true;
        }
    });

    if (!hasItems) {
        showAlert('warning', 'Silakan pilih minimal satu menu');
        return;
    }

    fetch('/kasir_restoran/api/orders/create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Pesanan berhasil dibuat!');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal membuat pesanan');
        }
    })
    .catch(error => {
        showAlert('danger', 'Terjadi kesalahan saat membuat pesanan');
    });
}

// Event listener saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    const availableTables = document.querySelectorAll('.badge.bg-success').length;
    const occupiedTables = document.querySelectorAll('.badge.bg-warning').length;
    
    showAlert('info', `Status Meja: ${availableTables} tersedia, ${occupiedTables} terisi`);
});

// Refresh halaman setiap 30 detik
let refreshTimeout = setTimeout(function() {
    location.reload();
}, 30000);

// Reset timeout saat user berinteraksi dengan modal
document.getElementById('createOrderModal').addEventListener('show.bs.modal', function() {
    clearTimeout(refreshTimeout);
});

document.getElementById('createOrderModal').addEventListener('hidden.bs.modal', function() {
    refreshTimeout = setTimeout(function() {
        location.reload();
    }, 30000);
});
</script>
