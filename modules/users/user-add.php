<?php
// Xử lý tạo User (Giữ nguyên logic PHP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $hub_id = !empty($_POST['hub_id']) ? intval($_POST['hub_id']) : null;
    $is_active = intval($_POST['is_active']);

    $insert_sql = "INSERT INTO users (email, password, full_name, phone, role, hub_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssii", $email, $password, $full_name, $phone, $role, $hub_id, $is_active);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Thêm tài khoản thành công!'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Lỗi: Cập nhật không thành công (Có thể Email đã tồn tại).');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// Lấy danh sách ENUM roles
$roles = [];
$role_sql = "SHOW COLUMNS FROM users LIKE 'role'";
$role_result = mysqli_query($conn, $role_sql);
if ($role_result) {
    $row = mysqli_fetch_assoc($role_result);
    preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
    if (!empty($matches[1])) {
        $roles = explode("','", $matches[1]);
    }
}
?>

<div class="modal-header">
    <h5>Thêm Nhân Sự Mới</h5>
    <button type="button" class="btn-close" onclick="closeAddModal()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>

<form method="POST" action="../../users/index.php">
    <div class="modal-body">
        <input type="hidden" name="action" value="add_user">

        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Họ và Tên</label>
                <input type="text" name="full_name" class="form-control" placeholder="Nhập họ và tên..." required>
            </div>
            <div class="form-col">
                <label class="form-label">Địa chỉ Email</label>
                <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="phone" class="form-control" placeholder="Nhập số điện thoại..." required>
            </div>
            <div class="form-col">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Vai trò hệ thống</label>
                <select name="role" class="form-select" required>
                    <option value="" disabled selected>-- Chọn vai trò --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $r))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-col">
                <label class="form-label">Mã Hub (Nếu có)</label>
                <input type="number" name="hub_id" class="form-control" placeholder="Bỏ trống nếu là Admin/Quản lý chung">
            </div>
        </div>

        <div class="form-row" style="margin-bottom: 0;">
            <div class="form-col">
                <label class="form-label">Trạng thái hoạt động</label>
                <select name="is_active" class="form-select">
                    <option value="1">Kích hoạt (Hoạt động)</option>
                    <option value="0">Tạm khóa (Ngưng hoạt động)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Hủy bỏ</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save" style="margin-right: 6px;"></i>Tạo Tài Khoản</button>
    </div>
</form>