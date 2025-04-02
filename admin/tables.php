<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <div id="alertPlaceholder" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 90%; width: fit-content;"></div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manajemen Meja</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTableModal">
                Tambah Meja
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nomor Meja</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM tables ORDER BY table_number");
                        while ($row = $stmt->fetch()) {
                            $statusClass = $row['status'] == 'available' ? 'success' : 'warning';
                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['table_number']}</td>";
                            echo "<td><span class='badge bg-{$statusClass}'>{$row['status']}</span></td>";
                            echo "<td>
                                    <button class='btn btn-sm btn-warning' onclick='editTable({$row['id']})'>Edit</button>
                                    <button class='btn btn-sm btn-danger' onclick='deleteTable({$row['id']})'>Hapus</button>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Meja -->
<div class="modal fade" id="addTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Meja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>    
                </button>
            </div>
            <div class="modal-body">
                <form id="addTableForm">
                    <div class="mb-3">
                        <label class="form-label">Nomor Meja</label>
                        <input type="text" class="form-control" name="table_number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveTable()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function saveTable() {
    const form = document.getElementById('addTableForm');
    const formData = new FormData(form);
    
    fetch('/kasir_restoran/api/tables/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showAlert('success', 'Meja berhasil ditambahkan');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal menambah meja');
        }
    });
}

function editTable(id) {
    // Ambil data meja
    fetch(`/kasir_restoran/api/tables/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const table = data.data;
                
                // Buat modal edit
                const modalHtml = `
                    <div class="modal fade" id="editTableModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Meja</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="editTableForm">
                                        <input type="hidden" name="id" value="${table.id}">
                                        <div class="mb-3">
                                            <label class="form-label">Nomor Meja</label>
                                            <input type="text" class="form-control" name="table_number" value="${table.table_number}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="available" ${table.status === 'available' ? 'selected' : ''}>Available</option>
                                                <option value="occupied" ${table.status === 'occupied' ? 'selected' : ''}>Occupied</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-primary" onclick="updateTable()">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Tambahkan modal ke body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Tampilkan modal
                const modal = new bootstrap.Modal(document.getElementById('editTableModal'));
                modal.show();
                
                // Hapus modal setelah ditutup
                document.getElementById('editTableModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }
        });
}

function updateTable() {
    const form = document.getElementById('editTableForm');
    const formData = new FormData(form);
    
    fetch('/kasir_restoran/api/tables/edit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showAlert('success', 'Meja berhasil diupdate');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal mengupdate meja');
        }
    });
}

function deleteTable(id) {
    showConfirm(
        'Konfirmasi Hapus',
        'Apakah Anda yakin ingin menghapus meja ini?',
        () => {
            fetch(`/kasir_restoran/api/tables/delete.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showAlert('success', 'Meja berhasil dihapus');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', data.message || 'Gagal menghapus meja');
                }
            })
            .catch(error => {
                showAlert('danger', 'Terjadi kesalahan saat menghapus meja');
            });
        }
    );
}
</script>
