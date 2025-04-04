<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'waiter') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';

// Ambil semua pesanan waiter ini
$stmt = $pdo->prepare("
    SELECT o.*, t.table_number,
    (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total
    FROM orders o 
    JOIN tables t ON o.table_id = t.id 
    WHERE o.waiter_id = ?
    ORDER BY o.order_time DESC
    LIMIT 100
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Daftar Pesanan</h2>
            <a href="tables.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Pesanan Baru
            </a>
        </div>

        <!-- Filter Pesanan -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status_filter">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="date_filter" 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary d-block" onclick="filterOrders()">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Pesanan -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="ordersTable">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>No. Meja</th>
                                <th>Waktu Pesan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo $order['table_number']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['order_time'])); ?></td>
                                <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?php echo $order['status'] == 'pending' ? 'bg-warning' : 'bg-success'; ?>">
                                        <?php echo $order['status'] == 'pending' ? 'Pending' : 'Selesai'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                        Detail
                                    </button>
                                    <?php if ($order['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="editOrder(<?php echo $order['id']; ?>)">
                                        Edit
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pesanan -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="orderDetails">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Pesanan -->
<div class="modal fade" id="editOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editOrderForm">
                    <input type="hidden" id="edit_order_id" name="order_id">
                    <div id="editMenuItems">
                        <!-- Menu items will be loaded here -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="updateOrder()">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function filterOrders() {
    const status = document.getElementById('status_filter').value;
    const date = document.getElementById('date_filter').value;
    const table = document.getElementById('ordersTable');
    const rows = table.getElementsByTagName('tr');
    let visibleRows = 0;

    for (let i = 1; i < rows.length; i++) {
        let show = true;
        const statusCell = rows[i].getElementsByTagName('td')[4];
        const dateCell = rows[i].getElementsByTagName('td')[2];

        if (status && !statusCell.textContent.toLowerCase().includes(status)) {
            show = false;
        }

        if (date) {
            const orderDate = new Date(dateCell.textContent.split(' ')[0].split('/').reverse().join('-'));
            const filterDate = new Date(date);
            if (orderDate.toDateString() !== filterDate.toDateString()) {
                show = false;
            }
        }

        rows[i].style.display = show ? '' : 'none';
        if (show) visibleRows++;
    }

    showAlert('success', `Menampilkan ${visibleRows} pesanan`);
}

function viewOrder(orderId) {
    fetch(`/kasir_restoran/api/orders/get_order.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.data);
                new bootstrap.Modal(document.getElementById('orderModal')).show();
            } else {
                showAlert('danger', data.message || 'Gagal memuat detail pesanan');
            }
        })
        .catch(error => {
            showAlert('danger', 'Terjadi kesalahan saat memuat detail pesanan');
        });
}

function editOrder(orderId) {
    fetch(`/kasir_restoran/api/orders/get_order.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_order_id').value = orderId;
                displayEditForm(data.data);
                new bootstrap.Modal(document.getElementById('editOrderModal')).show();
            } else {
                showAlert('danger', data.message || 'Gagal memuat data pesanan untuk diedit');
            }
        })
        .catch(error => {
            showAlert('danger', 'Terjadi kesalahan saat memuat data pesanan');
        });
}

function displayEditForm(order) {
    fetch('/kasir_restoran/api/menu/get_all.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="mb-3">';
                data.data.forEach(item => {
                    const orderItem = order.items.find(oi => oi.menu_id === item.id) || { quantity: 0 };
                    html += `
                        <div class="row mb-2 align-items-center">
                            <div class="col">
                                <label>${item.name}</label>
                                <br>
                                <small class="text-muted">Rp ${parseInt(item.price).toLocaleString('id-ID')}</small>
                            </div>
                            <div class="col-auto">
                                <div class="input-group" style="width: 120px;">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            onclick="updateEditQuantity(${item.id}, -1)">-</button>
                                    <input type="number" class="form-control form-control-sm text-center" 
                                           id="edit_qty_${item.id}" 
                                           name="items[${item.id}]" 
                                           value="${orderItem.quantity}" min="0" readonly>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            onclick="updateEditQuantity(${item.id}, 1)">+</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('editMenuItems').innerHTML = html;
            } else {
                showAlert('danger', data.message || 'Gagal memuat daftar menu');
            }
        })
        .catch(error => {
            showAlert('danger', 'Terjadi kesalahan saat memuat daftar menu');
        });
}

function updateEditQuantity(menuId, change) {
    const input = document.getElementById(`edit_qty_${menuId}`);
    let value = parseInt(input.value) + change;
    input.value = Math.max(0, value);
}

function updateOrder() {
    const formData = new FormData(document.getElementById('editOrderForm'));

    fetch('/kasir_restoran/api/orders/update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Pesanan berhasil diupdate!');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal mengupdate pesanan');
        }
    })
    .catch(error => {
        showAlert('danger', 'Terjadi kesalahan saat mengupdate pesanan');
    });
}

function displayOrderDetails(order) {
    let html = `
        <div class="mb-3">
            <table class="table table-sm">
                <tr>
                    <td>ID Pesanan</td>
                    <td>: ${order.id}</td>
                </tr>
                <tr>
                    <td>No. Meja</td>
                    <td>: ${order.table_number}</td>
                </tr>
                <tr>
                    <td>Waktu Pesan</td>
                    <td>: ${new Date(order.order_time).toLocaleString('id-ID')}</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>: ${order.status === 'pending' ? 'Pending' : 'Selesai'}</td>
                </tr>
            </table>
        </div>
        <div class="mb-3">
            <h6>Item Pesanan:</h6>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    order.items.forEach(item => {
        html += `
            <tr>
                <td>${item.menu_name}</td>
                <td>${item.quantity}</td>
                <td>Rp ${parseInt(item.price).toLocaleString('id-ID')}</td>
                <td>Rp ${(item.quantity * item.price).toLocaleString('id-ID')}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th>Rp ${parseInt(order.total).toLocaleString('id-ID')}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    document.getElementById('orderDetails').innerHTML = html;
}

// Tambahkan event listener untuk menampilkan pesan saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    const totalOrders = document.querySelectorAll('#ordersTable tbody tr').length;
    if (totalOrders > 0) {
        showAlert('info', `Terdapat ${totalOrders} pesanan dalam daftar`);
    }
});
</script>
