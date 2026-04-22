<?php
require "warehouse.php";

// Xử lý khi người dùng ấn nút "Lưu kho bãi" trên Modal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_add_warehouse'])) {
    $isAdded = warehouseAdd($_POST);
    if ($isAdded) {
        echo "<script> window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Không thể thêm kho bãi.');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_edit_warehouse'])) {
    $isEdited = warehouseEdit($_POST);
    if ($isEdited) {
        echo "<script> window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Không thể cập nhật kho bãi.');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_toggle_status'])) {
    $isToggled = warehouseToggleStatus($_POST);
    if ($isToggled) {
        // Load lại trang
        echo "<script>window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Lỗi: Không thể thay đổi trạng thái kho bãi.');</script>";
    }
}

$hubs = warehouseList();
$managers = ManagerHubList();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Mạng lưới Kho bãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="p-4">

<h2 class="mb-4">Danh mục Kho bãi</h2>

<button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addWarehouseModal">
    + Thêm kho mới
</button>

<table class="table table-bordered table-striped">
    <thead class="table-dark">
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
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if (!empty($hubs)):
            foreach ($hubs as $hub): 
        ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($hub['code']); ?></strong></td>
                <td><?php echo htmlspecialchars($hub['name']); ?></td>
                <td><?php echo htmlspecialchars($hub['type']); ?></td>
                <td><?php echo htmlspecialchars($hub['region']); ?></td>
                <td><?php echo htmlspecialchars($hub['address']); ?></td>
                <td>
                    <?php 
                    echo date("H:i", strtotime($hub['open_time'])) . 
                         " - " . 
                         date("H:i", strtotime($hub['close_time'])); 
                    ?>
                </td>
                <td><?php echo number_format($hub['max_capacity']); ?></td>
                <td><?php echo htmlspecialchars($hub['full_name']); ?></td>
                <td>
                    <?php if ($hub['is_active']): ?>
                        <span class="badge bg-success">Đang hoạt động</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Không hoạt động</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editWarehouseModal"
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
                    <form method="POST" action="index.php" style="display:inline-block;">
                    <input type="hidden" name="toggle_id" value="<?php echo $hub['id']; ?>">
                    <input type="hidden" name="current_status" value="<?php echo $hub['is_active']; ?>">
                    <button type="submit" name="submit_toggle_status" 
                            class="btn btn-sm <?php echo $hub['is_active'] ? 'btn-warning' : 'btn-info'; ?>"
                            onclick="return confirm('Bạn có chắc chắn muốn <?php echo $hub['is_active'] ? 'đóng băng (tắt)' : 'kích hoạt (bật)'; ?> kho này không?');">
                        <?php echo $hub['is_active'] ? 'Tắt' : 'Bật'; ?>
                    </button>
                </form>
                </td>
            </tr>
        <?php 
            endforeach; 
        else: 
        ?>
            <tr>
                <td colspan="10" align="center">Chưa có dữ liệu kho bãi.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'warehouse-add.php'; ?>
<?php include 'warehouse-edit.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr(".time-picker", {
            enableTime: true,       // Bật tính năng chọn giờ
            noCalendar: true,       // Tắt tính năng chọn ngày
            enableSeconds: true,    // BẬT TÍNH NĂNG CHỌN GIÂY
            dateFormat: "H:i:S",    // Định dạng xuất ra: Giờ:Phút:Giây (VD: 06:30:00)
            time_24hr: true,        // Ép buộc giao diện hiển thị dạng 24 giờ
            minuteIncrement: 1      // Nhảy từng 1 phút
        });
    });
    var editWarehouseModal = document.getElementById('editWarehouseModal');
    if (editWarehouseModal) {
        editWarehouseModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        // Trích xuất dữ liệu từ các thuộc tính data-*
        var id = button.getAttribute('data-id');
        var code = button.getAttribute('data-code');
        var name = button.getAttribute('data-name');
        var type = button.getAttribute('data-type');
        var region = button.getAttribute('data-region');
        var address = button.getAttribute('data-address');
        var openTime = button.getAttribute('data-opentime');
        var closeTime = button.getAttribute('data-closetime');
        var capacity = button.getAttribute('data-capacity');
        var managerId = button.getAttribute('data-manager');

        // Cập nhật vào các field trong form
        editWarehouseModal.querySelector('#edit_id').value = id;
        editWarehouseModal.querySelector('#edit_code').value = code;
        editWarehouseModal.querySelector('#edit_name').value = name;
        editWarehouseModal.querySelector('#edit_type').value = type;
        editWarehouseModal.querySelector('#edit_region').value = region;
        editWarehouseModal.querySelector('#edit_address').value = address;
        editWarehouseModal.querySelector('#edit_max_capacity').value = capacity;
        editWarehouseModal.querySelector('#edit_manager_id').value = managerId;
        
        // Thiết lập giá trị cho ô nhập liệu thời gian để thư viện Flatpickr nhận dạng trực tiếp định dạng hh:mm:ss
        editWarehouseModal.querySelector('#edit_open_time').value = openTime;
        editWarehouseModal.querySelector('#edit_close_time').value = closeTime;
    });
}
</script>
</body>
</html>