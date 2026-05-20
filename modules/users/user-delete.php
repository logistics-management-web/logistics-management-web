<?php
// Gọi session_start() nếu chưa được gọi (Cực kỳ quan trọng để kiểm tra $_SESSION)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra nếu có yêu cầu xóa
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // SỬA LỖI 1: Bẫy tự xóa chính mình (đổi 'user_id' thành 'id' cho khớp với file login.php)
    if (isset($_SESSION['id']) && $delete_id == $_SESSION['id']) {
        echo "<script>alert('LỖI BẢO MẬT: Bạn không thể tự xóa tài khoản đang đăng nhập của chính mình!'); window.location.href='?view=users';</script>";
        exit;
    }

    $delete_sql = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Xóa tài khoản thành công!'); window.location.href='?view=users';</script>";
            exit;
        } else {
            echo "<script>alert('Lỗi: Không thể xóa tài khoản này.'); window.location.href='?view=users';</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}
?>