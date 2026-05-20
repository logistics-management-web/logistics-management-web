<?php
// Xử lý tạo User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $email = trim($_POST['email']);
    $raw_password = $_POST['password']; // Lấy mật khẩu gốc để kiểm tra

    // ==========================================
    // BẪY LỖI BẢO MẬT SERVER-SIDE (PHP)
    // ==========================================
    $errors = [];
    if (strlen($raw_password) < 8) {
        $errors[] = "quá ngắn (yêu cầu ít nhất 8 ký tự)";
    }
    if (!preg_match("/[A-Z]/", $raw_password)) {
        $errors[] = "thiếu chữ cái in hoa";
    }
    if (!preg_match("/[a-z]/", $raw_password)) {
        $errors[] = "thiếu chữ cái in thường";
    }
    if (!preg_match("/[0-9]/", $raw_password)) {
        $errors[] = "thiếu chữ số";
    }
    if (!preg_match("/[\W_]/", $raw_password)) {
        $errors[] = "thiếu ký tự đặc biệt (VD: @, #, $, %...)";
    }

    if (!empty($errors)) {
        $error_msg = "Mật khẩu " . implode(", ", $errors) . "!";
        echo "<script>
            alert('Cảnh báo bảo mật: $error_msg');
            window.history.back();
        </script>";
        exit;
    }

    // Nếu mật khẩu hợp lệ -> Mã hóa
    $password = password_hash($raw_password, PASSWORD_DEFAULT);
    
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
            // Đã sửa 'index.php' thành '?view=users' để chống lỗi Not Found (404)
            echo "<script>alert('Thêm tài khoản thành công!'); window.location.href='?view=users';</script>";
        } else {
            echo "<script>alert('Lỗi: Cập nhật không thành công (Có thể Email đã tồn tại).'); window.history.back();</script>";
        }
        mysqli_stmt_close($stmt);
        exit;
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

<form method="POST" action="">
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
                <label class="form-label">Mật khẩu <span style="color: #ef4444;">*</span></label>
                <input type="password" id="add_password" name="password" class="form-control" placeholder="••••••••" required oninput="window.validatePassword()">
                <small id="password_error" style="color: #ef4444; display: none; font-size: 12px; margin-top: 4px; font-weight: 500;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Mật khẩu phải có ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.
                </small>
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

<script>
window.validatePassword = function() {
    const pwdInput = document.getElementById('add_password');
    const errorMsg = document.getElementById('password_error');
    
    // Tìm chuẩn nút Submit
    const submitBtn = pwdInput.closest('form').querySelector('button[type="submit"]');

    // Biểu thức Regex: Tối thiểu 8 ký tự, có ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[a-zA-Z\d\W_]{8,}$/;

    if (pwdInput.value.length > 0 && !regex.test(pwdInput.value)) {
        // Sai điều kiện: Hiển thị lỗi, khóa cứng nút
        pwdInput.style.borderColor = '#ef4444';
        errorMsg.style.display = 'block';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor = 'not-allowed';
        }
        return false;
    } else {
        // Hợp lệ: Xóa lỗi, mở khóa nút
        pwdInput.style.borderColor = '#e2e8f0'; // Hoặc màu viền mặc định của form-control
        errorMsg.style.display = 'none';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        }
        return true;
    }
}

// Bắt sự kiện form submit (Ngăn cản thao tác F12 bypass)
const formAdd = document.getElementById('add_password').closest('form');
if (formAdd) {
    formAdd.onsubmit = function(e) {
        if (!window.validatePassword()) {
            e.preventDefault();
            document.getElementById('add_password').focus();
        }
    };
}
</script>