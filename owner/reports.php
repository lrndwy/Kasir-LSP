<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alert.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: /kasir_restoran/");
    exit;
}
include '../includes/header.php';

// Default periode (7 hari terakhir)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
?>

<div class="d-flex">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="container-fluid py-4">
        <h2>Laporan Penjualan</h2>
        <!-- Filter Periode -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_date" name="end_date"
                               value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary d-block w-100" onclick="generateReport()">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-danger d-block w-100" onclick="printReport()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ringkasan Laporan -->
        <div class="row mb-4" id="reportSummary">
            <!-- Akan diisi oleh JavaScript -->
        </div>

        <!-- Detail Transaksi -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detail Transaksi</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>ID Transaksi</th>
                                <th>Kasir</th>
                                <th>Pelayan</th>
                                <th>No. Meja</th>
                                <th>Total</th>
                                <th>Metode Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsBody">
                            <!-- Akan diisi oleh JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    function generateReport() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        // Validasi tanggal
        if (!startDate || !endDate) {
            showAlert('warning', 'Mohon isi periode tanggal terlebih dahulu!');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            showAlert('error', 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir!');
            return;
        }

        fetch(`/kasir_restoran/api/reports/get_report.php?start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateReportSummary(data.summary);
                    updateTransactionsTable(data.transactions);
                    showAlert('success', 'Laporan berhasil diperbarui!');
                } else {
                    showAlert('error', 'Gagal mengambil data laporan!');
                }
            })
            .catch(error => {
                showAlert('error', 'Terjadi kesalahan saat mengambil data!');
                console.error(error);
            });
    }

    function updateReportSummary(summary) {
        const html = `
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Transaksi</h5>
                        <h3>${summary.total_transactions}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Total Pendapatan</h5>
                        <h3>Rp ${parseInt(summary.total_income).toLocaleString('id-ID')}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Rata-rata Transaksi</h5>
                        <h3>Rp ${parseInt(summary.average_transaction).toLocaleString('id-ID')}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Total Menu Terjual</h5>
                        <h3>${summary.total_items}</h3>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('reportSummary').innerHTML = html;
    }

    function updateTransactionsTable(transactions) {
        const tbody = document.getElementById('transactionsBody');
        tbody.innerHTML = '';

        if (transactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">Tidak ada transaksi pada periode ini</td>
                </tr>
            `;
            showAlert('info', 'Tidak ada transaksi yang ditemukan pada periode yang dipilih');
            return;
        }

        transactions.forEach(t => {
            tbody.innerHTML += `
                <tr>
                    <td>${new Date(t.transaction_time).toLocaleDateString('id-ID')}</td>
                    <td>${t.id}</td>
                    <td>${t.kasir_name}</td>
                    <td>${t.waiter_name}</td>
                    <td>${t.table_number}</td>
                    <td>Rp ${parseInt(t.total).toLocaleString('id-ID')}</td>
                    <td>${t.payment_method.toUpperCase()}</td>
                </tr>
            `;
        });
    }

    function downloadPDF() {
        showConfirm(
            'Download PDF',
            'Apakah Anda yakin ingin mengunduh laporan dalam format PDF?',
            function() {
                const startDate = document.getElementById('start_date')?.value || '';
                const endDate = document.getElementById('end_date')?.value || '';
                const category = document.getElementById('category')?.value || '';
                
                let url = `/kasir_restoran/generate_pdf.php?page=${getCurrentPage()}`;
                if (startDate) url += `&start_date=${startDate}`;
                if (endDate) url += `&end_date=${endDate}`;
                if (category) url += `&category=${category}`;
                
                window.open(url, '_blank');
                showAlert('success', 'File PDF sedang diunduh!');
            }
        );
    }

    function getCurrentPage() {
        const path = window.location.pathname;
        if (path.includes('index.php')) return 'dashboard';
        if (path.includes('reports.php')) return 'reports';
        if (path.includes('sales.php')) return 'sales';
        return '';
    }

    function exportToExcel() {
        showConfirm(
            'Export Excel',
            'Apakah Anda yakin ingin mengexport laporan ke Excel?',
            function() {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                
                if (!startDate || !endDate) {
                    showAlert('warning', 'Mohon isi periode tanggal terlebih dahulu!');
                    return;
                }
                
                window.location.href = `/kasir_restoran/api/reports/export_excel.php?start_date=${startDate}&end_date=${endDate}`;
                showAlert('success', 'File Excel sedang diunduh!');
            }
        );
    }

    function printReport() {
        showConfirm(
            'Print Laporan',
            'Apakah Anda yakin ingin mencetak laporan ini?',
            function() {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                
                if (!startDate || !endDate) {
                    showAlert('warning', 'Mohon isi periode tanggal terlebih dahulu!');
                    return;
                }

                // Buat tampilan cetak
                let printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Laporan Penjualan</title>
                            <link href="/kasir_restoran/assets/css/bootstrap.min.css" rel="stylesheet">
                            <style>
                                body { padding: 20px; }
                                @media print {
                                    .no-print { display: none; }
                                    .page-break { page-break-before: always; }
                                }
                                table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
                                th, td { padding: 8px; border: 1px solid #ddd; }
                                th { background-color: #f4f4f4; }
                                .text-end { text-align: right; }
                                .text-center { text-align: center; }
                                .mb-4 { margin-bottom: 1.5rem; }
                            </style>
                        </head>
                        <body>
                            <div class="text-center mb-4">
                                <h2>Laporan Penjualan</h2>
                                <p>Periode: ${new Date(startDate).toLocaleDateString('id-ID')} - ${new Date(endDate).toLocaleDateString('id-ID')}</p>
                            </div>
                            
                            <div class="mb-4">
                                <h4>Ringkasan</h4>
                                <table>
                                    <tr>
                                        <td>Total Transaksi</td>
                                        <td class="text-end">${document.querySelector('#reportSummary .bg-primary h3').textContent}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Pendapatan</td>
                                        <td class="text-end">${document.querySelector('#reportSummary .bg-success h3').textContent}</td>
                                    </tr>
                                    <tr>
                                        <td>Rata-rata Transaksi</td>
                                        <td class="text-end">${document.querySelector('#reportSummary .bg-info h3').textContent}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Menu Terjual</td>
                                        <td class="text-end">${document.querySelector('#reportSummary .bg-warning h3').textContent}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="page-break"></div>
                            <h4>Detail Transaksi</h4>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>ID</th>
                                        <th>Kasir</th>
                                        <th>Pelayan</th>
                                        <th>No. Meja</th>
                                        <th>Total</th>
                                        <th>Metode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${document.getElementById('transactionsBody').innerHTML}
                                </tbody>
                            </table>

                            <div class="no-print text-center mt-4">
                                <button onclick="window.print()" class="btn btn-primary">Print</button>
                                <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
                            </div>
                        </body>
                    </html>
                `);
                printWindow.document.close();
                showAlert('success', 'Halaman cetak telah dibuka!');
            }
        );
    }

    // Generate laporan saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        generateReport();
    });
</script>
