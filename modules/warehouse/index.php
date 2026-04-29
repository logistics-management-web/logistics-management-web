<?php
require "warehouse.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $success = false;
    $error_msg = "Đã xảy ra lỗi.";

    if (isset($_POST['submit_add_warehouse'])) {
        $success = warehouseAdd($_POST);
        $error_msg = "Không thể thêm kho bãi.";
    } elseif (isset($_POST['submit_edit_warehouse'])) {
        $success = warehouseEdit($_POST);
        $error_msg = "Không thể cập nhật kho bãi.";
    } elseif (isset($_POST['submit_toggle_status'])) {
        $success = warehouseToggleStatus($_POST);
        $error_msg = "Không thể thay đổi trạng thái.";
    } elseif (isset($_POST['submit_delete_warehouse'])) {
        $success = warehouseDelete($_POST);
        $error_msg = "Không thể xóa kho bãi.";
    }

    // Xử lý kết quả chung
    if ($success) {
        echo "<script>window.location.href='index.php';</script>";
        exit; // Dừng thực thi sau khi chuyển hướng
    } else {
        echo "<script>alert('$error_msg');</script>";
    }
}

$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$region = $_GET['region'] ?? '';
$manager_id = $_GET['manager_id'] ?? '';

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$total_records = warehouseCount($search, $type, $status, $region, $manager_id);
$total_pages = ceil($total_records / $limit);
$hubs = warehouseList($search, $type, $status, $region, $manager_id, $limit, $offset);

$managers = ManagerHubList();
disconnectDB();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Kho bãi - MD Logistic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

<div class="main-content">
    <div class="page-container">
        <div class="page-header">
            <div class="page-title">
                <h1>Quản trị Kho bãi</h1>
                <p>Quản lý, thiết lập và theo dõi mạng lưới kho bãi hệ thống.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="openModal('addWarehouseModal')">
                    + Thêm kho mới
                </button>
            </div>
        </div>

        <div class="filter-section">
            <form method="GET" action="index.php" id="filterForm" class="filter-form" autocomplete="off">
                <div class="search-box-filter">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Mã hoặc tên kho..." 
                           value="<?php echo htmlspecialchars($search); ?>" oninput="autoSubmit()">
                </div>
                
                <div class="search-box-filter" style="flex: 0.5;">
                    <input type="text" name="region" placeholder="Khu vực..." 
                           value="<?php echo htmlspecialchars($region); ?>" oninput="autoSubmit()">
                </div>

                <select name="type" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="">-- Loại kho --</option>
                    <option value="main_hub" <?php if($type === 'main_hub') echo 'selected'; ?>>Hub Chính</option>
                    <option value="station" <?php if($type === 'station') echo 'selected'; ?>>Trạm vệ tinh</option>
                </select>

                <select name="manager_id" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="">-- Quản lý --</option>
                    <?php foreach($managers as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php if($manager_id == $m['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($m['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" class="form-select filter-select" onchange="this.form.submit()">
                    <option value="">-- Trạng thái --</option>
                    <option value="1" <?php if($status === '1') echo 'selected'; ?>>Đang hoạt động</option>
                    <option value="0" <?php if($status === '0') echo 'selected'; ?>>Tạm ngưng</option>
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
                        <th>Mã kho</th>
                        <th>Tên kho</th>
                        <th>Loại</th>
                        <th>Khu vực</th>
                        <th>Địa chỉ</th>
                        <th>Giờ hoạt động</th>
                        <th>Tải trọng</th>
                        <th>Quản lý</th>
                        <th>Trạng thái</th>
                        <th style="text-align: right;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($hubs)): foreach ($hubs as $hub): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($hub['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($hub['name']); ?></td>
                            <td><?php echo htmlspecialchars($hub['type'] == 'main_hub' ? 'Hub Chính' : 'Trạm vệ tinh'); ?></td>
                            <td><?php echo htmlspecialchars($hub['region']); ?></td>
                            <td><?php echo htmlspecialchars($hub['address']); ?></td>
                            <td>
                                <?php echo date("H:i", strtotime($hub['open_time'])) . " - " . date("H:i", strtotime($hub['close_time'])); ?>
                            </td>
                            <td><?php echo number_format($hub['max_capacity']); ?></td>
                            <td><?php echo htmlspecialchars($hub['full_name']); ?></td>
                            <td>
                                <?php if ($hub['is_active']): ?>
                                    <span class="status-badge status-active">• Đang hoạt động</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">• Tạm ngưng</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button class="btn btn-sm btn-edit btn-edit-warehouse" 
                                    data-id="<?php echo $hub['id']; ?>"
                                    data-code="<?php echo htmlspecialchars($hub['code']); ?>"
                                    data-name="<?php echo htmlspecialchars($hub['name']); ?>"
                                    data-type="<?php echo htmlspecialchars($hub['type']); ?>"
                                    data-region="<?php echo htmlspecialchars($hub['region']); ?>"
                                    data-address="<?php echo htmlspecialchars($hub['address']); ?>"
                                    data-opentime="<?php echo $hub['open_time']; ?>"
                                    data-closetime="<?php echo $hub['close_time']; ?>"
                                    data-capacity="<?php echo $hub['max_capacity']; ?>"
                                    data-manager="<?php echo $hub['manager_id']; ?>">
                                    Sửa
                                </button>

                                <form method="POST" action="index.php" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="toggle_id" value="<?php echo $hub['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $hub['is_active']; ?>">
                                    
                                    <button type="submit" name="submit_toggle_status" 
                                            class="btn btn-sm <?php echo $hub['is_active'] ? 'btn-warning' : 'btn-info'; ?>">
                                        <?php echo $hub['is_active'] ? 'Tắt' : 'Bật'; ?>
                                    </button>
                                </form>

                                <button type="button" class="btn btn-sm btn-danger btn-delete-warehouse" 
                                        data-id="<?php echo $hub['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($hub['name']); ?>">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="10" align="center" style="padding: 30px;">Chưa có dữ liệu kho bãi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php 
                    // Lấy toàn bộ các tham số GET hiện tại
                    $queryParams = $_GET;
                    
                    // Loại bỏ tham số 'page' ra khỏi mảng để tránh bị lặp (ví dụ: ?page=2&page=3)
                    unset($queryParams['page']);
                    
                    // Build chuỗi query tự động
                    $built_query = http_build_query($queryParams);
                    
                    // Thêm dấu '&' ở đầu nếu mảng param không rỗng
                    $query_string = !empty($built_query) ? "&" . $built_query : "";

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

<?php include 'warehouse-add.php'; ?>
<?php include 'warehouse-edit.php'; ?>
<?php include 'warehouse-delete.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Khởi tạo thư viện chọn giờ
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr(".time-picker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 1
        });
    });

    // --- LOGIC ĐÓNG/MỞ MODAL BẰNG JAVASCRIPT THUẦN ---
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('show');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('show');
    }

    // Đóng modal khi click ra vùng xám bên ngoài
    window.onclick = function(event) {
        if (event.target.classList.contains('custom-modal')) {
            event.target.classList.remove('show');
        }
    }

    // Logic điền dữ liệu vào form Edit
    document.querySelectorAll('.btn-edit-warehouse').forEach(button => {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var code = this.getAttribute('data-code');
            var name = this.getAttribute('data-name');
            var type = this.getAttribute('data-type');
            var region = this.getAttribute('data-region');
            var address = this.getAttribute('data-address');
            var openTime = this.getAttribute('data-opentime');
            var closeTime = this.getAttribute('data-closetime');
            var capacity = this.getAttribute('data-capacity');
            var managerId = this.getAttribute('data-manager');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_code').value = code;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_type').value = type;
            document.getElementById('edit_region').value = region;
            document.getElementById('edit_address').value = address;
            document.getElementById('edit_open_time').value = openTime;
            document.getElementById('edit_close_time').value = closeTime;
            document.getElementById('edit_max_capacity').value = capacity;
            document.getElementById('edit_manager_id').value = managerId;

            openModal('editWarehouseModal');
        });
    });

    document.querySelectorAll('.btn-delete-warehouse').forEach(button => {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var name = this.getAttribute('data-name');

            document.getElementById('delete_id_input').value = id;
            document.getElementById('delete_hub_name').textContent = name;

            openModal('deleteWarehouseModal');
        });
    });
</script>
</body>
</html>