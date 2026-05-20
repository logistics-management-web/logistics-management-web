<?php
session_start();
// Nếu đã đăng nhập rồi thì đẩy thẳng vào trang điều phối
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ../orders/management/main_orders.php");
    exit;
}

require_once "../../config/config.php"; // Chú ý đường dẫn file config

// Gọi hàm kết nối database từ file db.php để khởi tạo biến $conn
connectDB();
global $conn;

$email = $password = "";
$email_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Kiểm tra Email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Vui lòng nhập email đăng nhập.";
    } else {
        $email = trim($_POST["email"]);
    }

    // 2. Kiểm tra Mật khẩu
    if (empty(trim($_POST["password"]))) {
        $password_err = "Vui lòng nhập mật khẩu.";
    } else {
        $password = trim($_POST["password"]);
    }

    // 3. Tiến hành truy vấn CSDL
    if (empty($email_err) && empty($password_err)) {
        // Chuyển parameter từ :email sang dấu ? cho tương thích với MySQLi
        $sql = "SELECT id, email, password, full_name, role FROM users WHERE email = ? AND is_active = 1";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind biến vào câu lệnh ("s" đại diện cho kiểu string)
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                // Lấy kết quả truy vấn
                $result = mysqli_stmt_get_result($stmt);

                // Nếu tìm thấy email trong CSDL
                if (mysqli_num_rows($result) == 1) {
                    if ($row = mysqli_fetch_assoc($result)) {
                        $id = $row["id"];
                        $email_db = $row["email"];
                        $db_password = $row["password"];
                        $full_name = $row["full_name"];
                        $role = $row["role"];

                        // =========================================================
                        // CƠ CHẾ KIỂM TRA MẬT KHẨU THÔNG MINH (HỖ TRỢ CẢ MẬT KHẨU CŨ)
                        // =========================================================
                        $login_success = false;

                        if (password_verify($password, $db_password)) {
                            // Trường hợp 1: Mật khẩu đã mã hóa kiểu mới chính xác
                            $login_success = true;
                        } elseif ($password === $db_password) {
                            // Trường hợp 2: Dự phòng tài khoản cũ lưu dạng văn bản thuần túy khớp nhau
                            $login_success = true;

                            // TỰ ĐỘNG NÂNG CẤP: Băm lại mật khẩu cũ và cập nhật vào CSDL để lần sau an toàn hơn
                            $new_hash = password_hash($password, PASSWORD_DEFAULT);
                            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                                mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $id);
                                mysqli_stmt_execute($update_stmt);
                                mysqli_stmt_close($update_stmt);
                            }
                        }

                        // Xử lý thiết lập Session khi một trong hai trường hợp trên đúng
                        if ($login_success) {
                            // Đăng nhập thành công, khởi tạo Session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $email_db;
                            $_SESSION["username"] = $full_name; // Lấy tên thật để hiển thị lên Navbar
                            $_SESSION["role"] = $role; // Lưu quyền để phân quyền sau này

                            // Đóng kết nối trước khi redirect
                            disconnectDB();

                            // Chuyển hướng tới trang Điều phối 
                            header("location: ../orders/management/main_orders.php");
                            exit;
                        } else {
                            $login_err = "Sai mật khẩu. Vui lòng kiểm tra lại.";
                        }
                    }
                } else {
                    $login_err = "Email không tồn tại hoặc tài khoản đã bị khóa.";
                }
            } else {
                echo "Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Đóng kết nối CSDL sau khi xử lý xong form
    disconnectDB();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Hệ thống</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="wrapper">
        <h2>Đăng Nhập</h2>
        <p class="subtitle">Hệ thống Quản lý Logistics (LogiScore)</p>

        <?php
        if (!empty($login_err)) {
            echo '<div class="alert-error">' . $login_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Email đăng nhập</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                    placeholder="VD: trandai@logiscore.vn">
                <span class="error-msg"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password">
                <span class="error-msg"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Đăng nhập">
            </div>
            <div class="link-text" style="margin-top: 15px;">
                Quên mật khẩu? <a href="javascript:void(0)" onclick="toggleForgotForm()"
                    style="color: #4318ff; font-weight: bold; text-decoration: none;">Khôi phục ngay</a>.
            </div>
        </form>
        <div id="forgot-password-box"
            style="display: none; margin-top: 20px; padding: 16px; background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; text-align: left; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <h4 style="margin: 0 0 8px 0; color: #1b2559; font-size: 15px;">Yêu cầu cấp lại mật khẩu</h4>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 12px; line-height: 1.4;">
                Nhập email tài khoản của bạn. Hệ thống sẽ ghi nhận và Quản trị viên sẽ liên hệ lại để cấp mật khẩu mới.
            </p>

            <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                <input type="email" id="reset_email_input" placeholder="Nhập địa chỉ email..."
                    style="flex: 1; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 13px; color: #1e293b;">
                <button type="button" onclick="submitResetRequest()"
                    style="background: #ef4444; color: white; border: none; border-radius: 6px; padding: 0 16px; cursor: pointer; font-weight: bold; font-size: 13px; transition: 0.2s;">
                    <i class="fa-solid fa-paper-plane" style="margin-right: 4px;"></i> Gửi
                </button>
            </div>

            <div
                style="font-size: 13px; color: #334155; border-top: 1px dashed #cbd5e1; padding-top: 12px; text-align: center;">
                <span style="display: block; margin-bottom: 6px; font-weight: 500;">Hoặc liên hệ trực tiếp bộ phận
                    IT:</span>
                <div style="display: flex; justify-content: center; gap: 16px;">
                    <span><i class="fa-solid fa-phone" style="color: #4318ff;"></i> <strong
                            style="color: #1b2559;">0909.123.456</strong></span>
                    <span><i class="fa-solid fa-envelope" style="color: #4318ff;"></i> <strong
                            style="color: #1b2559;">admin@logiscore.vn</strong></span>
                </div>
            </div>
        </div>

        <script>
            function toggleForgotForm() {
                var box = document.getElementById("forgot-password-box");
                // Hiệu ứng bật/tắt mượt mà
                if (box.style.display === "none") {
                    box.style.display = "block";
                    document.getElementById("reset_email_input").focus(); // Tự động trỏ chuột vào ô nhập liệu
                } else {
                    box.style.display = "none";
                }
            }

            function submitResetRequest() {
                var emailInput = document.getElementById("reset_email_input").value;

                // Bẫy lỗi nếu để trống
                if (emailInput.trim() === "") {
                    alert("Vui lòng nhập địa chỉ email của bạn trước khi gửi!");
                    document.getElementById("reset_email_input").focus();
                    return;
                }

                // Bẫy lỗi định dạng email cơ bản
                if (!emailInput.includes("@") || !emailInput.includes(".")) {
                    alert("Địa chỉ email không hợp lệ. Vui lòng kiểm tra lại!");
                    document.getElementById("reset_email_input").focus();
                    return;
                }

                // Báo cáo thành công (Mô phỏng gửi đi)
                alert("Yêu cầu khôi phục mật khẩu cho tài khoản [" + emailInput + "] đã được gửi đến hệ thống.\n\nVui lòng chờ Quản trị viên liên hệ hoặc gọi Hotline nếu cần gấp!");

                // Xóa trắng ô nhập liệu và đóng form
                document.getElementById("reset_email_input").value = "";
                toggleForgotForm();
            }
        </script>
        </form>
    </div>
</body>

</html>