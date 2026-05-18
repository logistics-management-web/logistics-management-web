<?php
require "rates/rates.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_pricing') {
    // Chỉ accountant mới có quyền update bảng giá
    if (!check_permission('pricing', 'edit')) {
        die("Hành động bị cấm: Bạn không có quyền thay đổi cấu hình cước phí hệ thống.");
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_add_rate'])) {
    if (rateAdd($_POST)) echo "<script> window.location.href='index.php';</script>";
    else echo "<script>alert('Lỗi: Không thể thêm bảng giá.');</script>";
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_edit_rate'])) {
    if (rateEdit($_POST)) echo "<script> window.location.href='index.php';</script>";
    else echo "<script>alert('Lỗi: Không thể cập nhật bảng giá.');</script>";
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_delete_rate'])) {
    if (rateDelete($_POST)) echo "<script> window.location.href='index.php';</script>";
    else echo "<script>alert('Lỗi: Bảng giá này đang liên kết tuyến đường.');</script>";
}
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1; 
}
$offset = ($page - 1) * $limit;
// KẾT THÚC SỬA

$total_records = rateCount($search, $status);
$total_pages = ceil($total_records / $limit);

$rates = rateList($search, $status, $limit, $offset);
disconnectDB();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cấu hình Bảng giá cước</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css"> </head>
<body>

<div class="main-content">
    <div class="page-container">
        <div class="page-header">
            <div class="page-title">
                <h1>Danh mục Bảng giá cước</h1>
                <p>Quản lý các phiên bản bảng giá cước phí vận chuyển.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="openModal('addRateModal')">
                    + Thêm bảng giá mới
                </button>
            </div>
        </div>

        <div class="filter-section">
            <form method="GET" action="index.php" id="filterForm" class="filter-form">
                <div class="search-box-filter">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Mã hoặc tên bảng giá..." 
                           value="<?php echo htmlspecialchars($search); ?>" oninput="autoSubmit()">
                </div>

                <select name="status" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="">-- Trạng thái --</option>
                    <option value="active" <?php if($status === 'active') echo 'selected'; ?>>Đang áp dụng</option>
                    <option value="draft" <?php if($status === 'draft') echo 'selected'; ?>>Bản nháp</option>
                    <option value="archived" <?php if($status === 'archived') echo 'selected'; ?>>Lưu trữ</option>
                </select>

                <a href="index.php" class="btn btn-secondary">Xóa lọc</a>
            </form>
        </div>

        <script>
            // Hàm tự động submit khi gõ, có delay 500ms để không load liên tục
            let timer;
            function autoSubmit() {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500); 
            }
        </script>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Mã bảng giá</th>
                        <th>Tên bảng giá</th>
                        <th>Phiên bản</th>
                        <th>Ngày hiệu lực</th>
                        <th>Trạng thái</th>
                        <th style="text-align: right;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rates)): foreach ($rates as $rate): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rate['code_rates']); ?></td>
                            <td><strong><?php echo htmlspecialchars($rate['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($rate['version']); ?></td>
                            <td><?php echo date("d/m/Y", strtotime($rate['effective_date'])); ?></td>
                            <td>
                                <?php if ($rate['status'] == 'active'): ?>
                                    <span class="status-badge status-active">Đang áp dụng</span>
                                <?php elseif ($rate['status'] == 'draft'): ?>
                                    <span class="status-badge status-draft">Bản nháp</span>
                                <?php else: ?>
                                    <span class="status-badge status-archived">Lưu trữ</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button class="btn btn-sm btn-edit btn-edit-rate" 
                                    data-id="<?php echo $rate['id']; ?>"
                                    data-code="<?php echo htmlspecialchars($rate['code_rates']); ?>"
                                    data-name="<?php echo htmlspecialchars($rate['name']); ?>"
                                    data-version="<?php echo htmlspecialchars($rate['version']); ?>"
                                    data-status="<?php echo htmlspecialchars($rate['status']); ?>"
                                    data-effectivedate="<?php echo $rate['effective_date']; ?>">
                                    Sửa
                                </button>
                                <a href="rates/rate_details.php?id=<?php echo $rate['id']; ?>" class="btn btn-sm btn-info">Chi tiết</a>
                                
                                <button type="button" class="btn btn-sm btn-danger btn-delete-rate" 
                                        data-id="<?php echo $rate['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($rate['name']); ?>">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" align="center">Chưa có dữ liệu.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php 
                    $query_string = "&search=".urlencode($search)."&type="."&status=".urlencode($status);
                    for ($i = 1; $i <= $total_pages; $i++): 
                    ?>
                        <a href="?page=<?php echo $i . $query_string; ?>" 
                           class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'rates/rates-add.php'; ?>
<?php include 'rates/rates-edit.php'; ?>
<?php include 'rates/rates-delete.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr(".date-picker", { dateFormat: "Y-m-d" });
    });

    function openModal(id) { document.getElementById(id).classList.add('show'); }
    function closeModal(id) { document.getElementById(id).classList.remove('show'); }

    document.querySelectorAll('.btn-edit-rate').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_code_rates').value = this.getAttribute('data-code');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_version').value = this.getAttribute('data-version');
            document.getElementById('edit_status').value = this.getAttribute('data-status');
            document.getElementById('edit_effective_date').value = this.getAttribute('data-effectivedate');
            openModal('editRateModal');
        });
    });

    document.querySelectorAll('.btn-delete-rate').forEach(button => {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var name = this.getAttribute('data-name');

            document.getElementById('delete_rate_id_input').value = id;
            document.getElementById('delete_rate_name').textContent = name;

            openModal('deleteRateModal');
        });
    });
</script>
</body>
</html>