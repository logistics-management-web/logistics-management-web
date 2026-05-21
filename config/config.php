<?php
global $conn;
if (isset($_SESSION['user_id']) && isset($_SESSION['is_first_login'])) {
    if ($_SESSION['is_first_login'] == 1 && basename($_SERVER['PHP_SELF']) != 'change_password.php') {
        // Cưỡng chế quay lại trang đổi mật khẩu nếu chưa đổi
        header("Location: /path/to/modules/login/change_password.php");
        exit();
    }
}   

function connectDB() {
    global $conn;
    if (!$conn) {
        $conn = @mysqli_connect("34.126.147.241", "md162", "Logistic@2026", "logistic");
        
        if (!$conn) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối CSDL']);
            exit;
        }
        mysqli_set_charset($conn, "utf8mb4");
    }
}

function disconnectDB() {
    global $conn;
    if ($conn) {
        mysqli_close($conn);
    }
}
require_once __DIR__ . '/permissions.php';
?>
