<?php
// Kiểm tra nếu có yêu cầu xóa gửi tới trang index.php
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Bảo vệ: Không cho phép quản trị viên tự xóa chính mình
    if (isset($_SESSION['user_id']) && $delete_id == $_SESSION['user_id']) {
        echo "<script>alert('Xóa tài khoản thành công!'); window.location.href='../orders/management/main_orders.php?view=users';</script>";
        exit;
    }

    $delete_sql = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Xóa tài khoản thành công!'); window.location.href='index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Lỗi: Không thể xóa tài khoản này.'); window.location.href='index.php';</script>";
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}
?>