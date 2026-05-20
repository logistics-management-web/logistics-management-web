<?php
// Xử lý Cập nhật User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    $id = intval($_POST['id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $hub_id = !empty($_POST['hub_id']) ? intval($_POST['hub_id']) : null;
    $is_active = intval($_POST['is_active']);

    if (!empty($_POST['new_password'])) {
        $raw_password = $_POST['new_password'];
        
        // BẪY LỖI BẢO MẬT: Kiểm tra độ khó mật khẩu mới (Server-side)
        $errors = [];
        if (strlen($raw_password) < 8) $errors[] = "quá ngắn (yêu cầu ít nhất 8 ký tự)";
        if (!preg_match("/[A-Z]/", $raw_password)) $errors[] = "thiếu chữ cái in hoa";
        if (!preg_match("/[a-z]/", $raw_password)) $errors[] = "thiếu chữ cái in thường";
        if (!preg_match("/[0-9]/", $raw_password)) $errors[] = "thiếu chữ số";
        if (!preg_match("/[\W_]/", $raw_password)) $errors[] = "thiếu ký tự đặc biệt";

        if (!empty($errors)) {
            $error_msg = "Mật khẩu mới " . implode(", ", $errors) . "!";
            echo "<script>alert('Cảnh báo: $error_msg'); window.history.back();</script>";
            exit;
        }

        $new_password = password_hash($raw_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, hub_id=?, is_active=?, password=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $update_sql);
        
        // ĐÃ SỬA LỖI Ở ĐÂY: Dùng "ssssiisi" để biến $new_password được lưu đúng dạng Chuỗi (s)
        // 4 chuỗi đầu (s), hub_id (i), is_active (i), password (s), id (i) = ssssiisi
        mysqli_stmt_bind_param($stmt, "ssssiisi", $full_name, $email, $phone, $role, $hub_id, $is_active, $new_password, $id);
    } else {
        $update_sql = "UPDATE users SET full_name=?, email=?, phone=?, role=?, hub_id=?, is_active=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $update_sql);
        
        // ĐÃ SỬA LỖI Ở ĐÂY: Dùng "ssssiii"
        mysqli_stmt_bind_param($stmt, "ssssiii", $full_name, $email, $phone, $role, $hub_id, $is_active, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        // Đã sửa lại URL redirect để không bị lỗi Not Found 404
        echo "<script>alert('Cập nhật tài khoản thành công!'); window.location.href='?view=users';</script>";
        exit;
    } else {
        echo "<script>alert('Lỗi: Không thể cập nhật thông tin.'); window.history.back();</script>";
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

<form method="POST" action="">
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
                <input type="password" name="new_password" id="edit_new_password" class="form-control" placeholder="Để trống nếu giữ nguyên" oninput="window.validateEditPassword()">
                <small id="edit_password_error" style="color: #ef4444; display: none; font-size: 12px; margin-top: 4px; font-weight: 500;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Mật khẩu phải có ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
                </small>
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

<script>
// Logic kiểm tra mật khẩu giao diện (chỉ kích hoạt nếu người dùng có gõ chữ vào ô)
window.validateEditPassword = function() {
    const pwdInput = document.getElementById('edit_new_password');
    const errorMsg = document.getElementById('edit_password_error');
    const submitBtn = pwdInput.closest('form').querySelector('button[type="submit"]');
    
    // Yêu cầu: Tối thiểu 8 ký tự, 1 hoa, 1 thường, 1 số, 1 ký tự đặc biệt
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[a-zA-Z\d\W_]{8,}$/;

    // Chỉ bắt lỗi nếu người dùng thực sự muốn đổi mật khẩu (có nhập liệu)
    if (pwdInput.value.length > 0 && !regex.test(pwdInput.value)) {
        pwdInput.style.borderColor = '#ef4444';
        errorMsg.style.display = 'block';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        }
        return false;
    } else {
        // Hợp lệ hoặc ô trống (giữ nguyên mật khẩu cũ)
        pwdInput.style.borderColor = '#e2e8f0';
        errorMsg.style.display = 'none';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
        return true;
    }
}

// Bắt sự kiện form submit an toàn
const formEdit = document.getElementById('edit_new_password').closest('form');
if (formEdit) {
    // Lưu ý: Dùng addEventListener ở đây để không ghi đè lên onsubmit của hệ thống AJAX ở index.php
    formEdit.addEventListener('submit', function(e) {
        if (!window.validateEditPassword()) {
            e.preventDefault();
            document.getElementById('edit_new_password').focus();
        }
    });
}
</script>