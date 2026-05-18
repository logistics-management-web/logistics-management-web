<?php

// Xử lý Cập nhật User (Giữ nguyên logic PHP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    $id = intval($_POST['id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $hub_id = !empty($_POST['hub_id']) ? intval($_POST['hub_id']) : null;
    $is_active = intval($_POST['is_active']);

    if (!empty($_POST['new_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, hub_id=?, is_active=?, password=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sssssiii", $full_name, $email, $phone, $role, $hub_id, $is_active, $new_password, $id);
    } else {
        $update_sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, hub_id=?, is_active=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "sssssii", $full_name, $email, $phone, $role, $hub_id, $is_active, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Cập nhật tài khoản thành công!'); window.location.href='../orders/management/main_orders.php?view=users';</script>";
        exit;
    } else {
        echo "<script>alert('Lỗi: Không thể cập nhật thông tin.');</script>";
    }
    mysqli_stmt_close($stmt);
}

// Lấy danh sách ENUM (Sử dụng lại logic của form add nếu cần, hoặc truy vấn riêng)
$enum_roles = [];
$role_sql_edit = "SHOW COLUMNS FROM users LIKE 'role'";
$role_result_edit = mysqli_query($conn, $role_sql_edit);
if ($role_result_edit) {
    $row_edit = mysqli_fetch_assoc($role_result_edit);
    preg_match("/^enum\(\'(.*)\'\)$/", $row_edit['Type'], $matches);
    if (!empty($matches[1])) {
        $enum_roles = explode("','", $matches[1]);
    }
}
?>

<div class="modal-header">
    <h5>Cập Nhật Nhân Sự</h5>
    <button type="button" class="btn-close" onclick="closeEditModal()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>

<form method="POST" action="../../users/index.php">
    <div class="modal-body">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="id" id="edit_id" value="">

        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Họ và Tên</label>
                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
            </div>
            <div class="form-col">
                <label class="form-label">Địa chỉ Email</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="phone" id="edit_phone" class="form-control" required>
            </div>
            <div class="form-col">
                <label class="form-label">Đổi mật khẩu mới</label>
                <input type="password" name="new_password" id="edit_new_password" class="form-control" placeholder="Để trống nếu giữ nguyên">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-col">
                <label class="form-label">Vai trò hệ thống</label>
                <select name="role" id="edit_role" class="form-select" required>
                    <?php foreach ($enum_roles as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>">
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $r))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-col">
                <label class="form-label">Mã Hub (Nếu có)</label>
                <input type="number" name="hub_id" id="edit_hub_id" class="form-control" placeholder="Trống nếu không thuộc Hub">
            </div>
        </div>

        <div class="form-row" style="margin-bottom: 0;">
            <div class="form-col">
                <label class="form-label">Trạng thái hoạt động</label>
                <select name="is_active" id="edit_is_active" class="form-select">
                    <option value="1">Kích hoạt (Hoạt động)</option>
                    <option value="0">Tạm khóa (Ngưng hoạt động)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Hủy bỏ</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check" style="margin-right: 6px;"></i>Cập Nhật</button>
    </div>
</form>