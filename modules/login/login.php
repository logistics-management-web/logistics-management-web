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
                        
                        // KIỂM TRA MẬT KHẨU
                        if ($password === $db_password) {
                            
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
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="VD: trandai@logiscore.vn">
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
            <div class="link-text">
                Quên mật khẩu? <a href="#">Liên hệ Quản trị viên</a>.
            </div>
        </form>
    </div>
</body>
</html>