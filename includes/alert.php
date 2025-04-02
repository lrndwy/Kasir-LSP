<?php
function myalert($type, $message) {
    $alertClass = '';
    $icon = '';
    
    switch($type) {
        case 'success':
            $alertClass = 'alert-success';
            $icon = 'fa-check-circle';
            break;
        case 'error':
            $alertClass = 'alert-danger'; 
            $icon = 'fa-times-circle';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            $icon = 'fa-exclamation-triangle'; 
            break;
        case 'info':
            $alertClass = 'alert-info';
            $icon = 'fa-info-circle';
            break;
        default:
            $alertClass = 'alert-secondary';
            $icon = 'fa-info-circle';
    }

    echo "<div class='alert $alertClass alert-dismissible fade show animate__animated animate__fadeIn' role='alert'>
            <i class='fas $icon me-2'></i>
            $message
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
}
?>

<script>
function showAlert(type, message) {
    const alertPlaceholder = document.getElementById('alertPlaceholder');
    
    const iconMap = {
        'success': 'check-circle',
        'danger': 'times-circle', 
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    
    const icon = iconMap[type] || 'info-circle';
    
    const alertHtml = `
        <div class='alert alert-${type} alert-dismissible fade show animate__animated animate__fadeIn' role='alert'>
            <i class='fas fa-${icon} me-2'></i>
            ${message}
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'>
            <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    alertPlaceholder.innerHTML = '';
    alertPlaceholder.insertAdjacentHTML('beforeend', alertHtml);
    
    setTimeout(() => {
        const alert = alertPlaceholder.querySelector('.alert');
        if (alert) {
            alert.classList.add('animate__fadeOut');
            setTimeout(() => {
                alertPlaceholder.innerHTML = '';
            }, 500);
        }
    }, 3000);
}

function showConfirm(title, message, onConfirm) {
    const modalId = 'confirmModal';
    
    // Hapus modal lama jika ada
    const oldModal = document.getElementById(modalId);
    if (oldModal) oldModal.remove();
    
    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="confirmBtn">Ya</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    
    document.getElementById('confirmBtn').onclick = () => {
        modal.hide();
        onConfirm();
    };
    
    modal.show();
    
    document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}
</script>
