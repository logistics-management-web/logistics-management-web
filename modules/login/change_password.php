<?php
session_start();

// Kiểm tra session: Nếu chưa đăng nhập hoặc không phải lần đăng nhập đầu tiên thì đẩy về trang điều phối
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_first_login"]) || $_SESSION["is_first_login"] != 1) {
    header("location: ../orders/management/main_orders.php");
    exit;
}

require_once "../../config/config.php";

// Kết nối database theo chuẩn của hệ thống
connectDB();
global $conn;

$new_password = $confirm_password = "";
$password_err = $success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // ==========================================
    // BẪY LỖI ĐỊNH DẠNG MẬT KHẨU MỚI
    // ==========================================
    if (strlen($new_password) < 6) {
        $password_err = "Mật khẩu phải có ít nhất 6 ký tự.";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $password_err = "Mật khẩu phải chứa ít nhất 1 chữ cái viết hoa (A-Z).";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $password_err = "Mật khẩu phải chứa ít nhất 1 chữ cái viết thường (a-z).";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $password_err = "Mật khẩu phải chứa ít nhất 1 chữ số (0-9).";
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $new_password)) {
        $password_err = "Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt (VD: @, #, $, %, ^, &...).";
    } elseif ($new_password !== $confirm_password) {
        $password_err = "Mật khẩu xác nhận không khớp.";
    } else {
        // ==========================================
        // VƯỢT QUA BẪY LỖI -> TIẾN HÀNH CẬP NHẬT
        // ==========================================
        
        // Băm mật khẩu để bảo mật
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Sử dụng session id đã được lưu từ login.php
        $user_id = $_SESSION["id"];

        // Cập nhật mật khẩu mới và tắt cờ is_first_login
        $sql = "UPDATE users SET password = ?, is_first_login = 0 WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Cập nhật lại session để cho phép truy cập các trang khác
                $_SESSION["is_first_login"] = 0;
                $success_msg = "Đổi mật khẩu thành công! Đang chuyển hướng vào hệ thống...";
                
                // Đóng kết nối
                disconnectDB();
                
                // Tự động chuyển hướng về trang điều phối đơn hàng sau 2 giây
                header("refresh:2;url=../orders/management/main_orders.php");
            } else {
                $password_err = "Có lỗi xảy ra khi cập nhật mật khẩu. Vui lòng liên hệ IT.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi Mật Khẩu Lần Đầu</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }
        .password-rules {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Bảo Mật Tài Khoản</h2>
        <p class="subtitle">Đây là lần đăng nhập đầu tiên. Vui lòng đổi mật khẩu để tiếp tục sử dụng hệ thống.</p>

        <?php
        if (!empty($password_err)) {
            echo '<div class="alert-error">' . $password_err . '</div>';
        }
        if (!empty($success_msg)) {
            echo '<div class="alert-success">' . $success_msg . '</div>';
        }
        ?>

        <?php if (empty($success_msg)): ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Mật khẩu mới</label>
                <input type="password" name="new_password" required 
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{6,}" 
                       title="Mật khẩu phải từ 6 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt" 
                       placeholder="Nhập mật khẩu an toàn">
                
                <div class="password-rules">
                    Yêu cầu: ≥ 6 ký tự, gồm chữ hoa, chữ thường, số & ký tự đặc biệt.
                </div>
            </div>
            
            <div class="form-group">
                <label>Xác nhận mật khẩu mới</label>
                <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới">
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Xác nhận & Tiếp tục">
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>