<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /");
    exit;
}
include '../includes/header.php';
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <div id="alertPlaceholder" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 90%; width: fit-content;"></div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manajemen Pengguna</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                Tambah Pengguna
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['username']}</td>";
                            echo "<td>{$row['role']}</td>";
                            echo "<td>{$row['created_at']}</td>";
                            echo "<td>
                                    <button class='btn btn-sm btn-warning' onclick='editUser({$row['id']})'>Edit</button>
                                    <button class='btn btn-sm btn-danger' onclick='deleteUser({$row['id']})'>Hapus</button>
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

<!-- Modal Tambah Pengguna -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="waiter">Waiter</option>
                            <option value="kasir">Kasir</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function saveUser() {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);
    
    fetch('/kasir_restoran/api/users/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showAlert('success', 'Pengguna berhasil ditambahkan');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal menambah pengguna');
        }
    });
}

function editUser(id) {
    // Ambil data user
    fetch(`/kasir_restoran/api/users/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const user = data.data;
                
                // Buat modal edit
                const modalHtml = `
                    <div class="modal fade" id="editUserModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Pengguna</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="editUserForm">
                                        <input type="hidden" name="id" value="${user.id}">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="username" value="${user.username}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Role</label>
                                            <select class="form-select" name="role" required>
                                                <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                                                <option value="waiter" ${user.role === 'waiter' ? 'selected' : ''}>Waiter</option>
                                                <option value="kasir" ${user.role === 'kasir' ? 'selected' : ''}>Kasir</option>
                                                <option value="owner" ${user.role === 'owner' ? 'selected' : ''}>Owner</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-primary" onclick="updateUser()">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Tambahkan modal ke body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Tampilkan modal
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
                
                // Hapus modal setelah ditutup
                document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }
        });
}

function updateUser() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    
    fetch('/kasir_restoran/api/users/edit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showAlert('success', 'Pengguna berhasil diupdate');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal mengupdate pengguna');
        }
    });
}

function deleteUser(id) {
    showConfirm(
        'Konfirmasi Hapus',
        'Apakah Anda yakin ingin menghapus pengguna ini?',
        () => {
            fetch(`/kasir_restoran/api/users/delete.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showAlert('success', 'Pengguna berhasil dihapus');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', data.message || 'Gagal menghapus pengguna');
                }
            })
            .catch(error => {
                showAlert('danger', 'Terjadi kesalahan saat menghapus pengguna');
            });
        }
    );
}
</script>
