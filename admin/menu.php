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
            <h2>Manajemen Menu</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                Tambah Menu
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Menu</th>
                            <th>Harga</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM menu WHERE is_active = 1 ORDER BY category, name");
                        while ($row = $stmt->fetch()) {
                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['name']}</td>";
                            echo "<td>Rp " . number_format($row['price'], 0, ',', '.') . "</td>";
                            echo "<td>{$row['category']}</td>";
                            echo "<td>
                                    <button class='btn btn-sm btn-warning' onclick='editMenu({$row['id']})'>Edit</button>
                                    <button class='btn btn-sm btn-danger' onclick='deleteMenu({$row['id']})'>Hapus</button>
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

<!-- Modal Tambah Menu -->
<div class="modal fade" id="addMenuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="addMenuForm">
                    <div class="mb-3">
                        <label class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga</label>
                        <input type="number" class="form-control" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category" required>
                            <option value="makanan">Makanan</option>
                            <option value="minuman">Minuman</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveMenu()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>

<script>
function saveMenu() {
    const form = document.getElementById('addMenuForm');
    const formData = new FormData(form);
    
    fetch('/kasir_restoran/api/menu/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showAlert('success', 'Menu berhasil ditambahkan');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal menambah menu');
        }
    });
}

function editMenu(id) {
    // Ambil data menu
    fetch(`/kasir_restoran/api/menu/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const menu = data.data;
                
                // Buat modal edit
                const modalHtml = `
                    <div class="modal fade" id="editMenuModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Menu</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="editMenuForm">
                                        <input type="hidden" name="id" value="${menu.id}">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Menu</label>
                                            <input type="text" class="form-control" name="name" value="${menu.name}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Harga</label>
                                            <input type="number" class="form-control" name="price" value="${menu.price}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Kategori</label>
                                            <select class="form-select" name="category" required>
                                                <option value="makanan" ${menu.category === 'makanan' ? 'selected' : ''}>Makanan</option>
                                                <option value="minuman" ${menu.category === 'minuman' ? 'selected' : ''}>Minuman</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-primary" onclick="updateMenu()">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Tambahkan modal ke body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Tampilkan modal
                const modal = new bootstrap.Modal(document.getElementById('editMenuModal'));
                modal.show();
                
                // Hapus modal setelah ditutup
                document.getElementById('editMenuModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }
        });
}

function updateMenu() {
    const form = document.getElementById('editMenuForm');
    const formData = new FormData(form);
    
    fetch('/kasir_restoran/api/menu/edit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showAlert('success', 'Menu berhasil diupdate');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert('danger', data.message || 'Gagal mengupdate menu');
        }
    });
}

function deleteMenu(id) {
    showConfirm(
        'Konfirmasi Hapus',
        'Apakah Anda yakin ingin menghapus menu ini?',
        () => {
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('/kasir_restoran/api/menu/delete.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showAlert('success', 'Menu berhasil dihapus');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', data.message || 'Gagal menghapus menu');
                }
            })
            .catch(error => {
                showAlert('danger', 'Terjadi kesalahan saat menghapus menu');
            });
        }
    );
}
</script>
