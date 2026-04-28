<?php
global $conn;

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
?>