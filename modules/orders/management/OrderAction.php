<?php
require_once '../../../config/config.php';
global $conn;
connectDB();
ob_clean();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');

$action = $_POST['action'] ?? '';

if ($action === 'check_phone') {
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $sql = "SELECT full_name FROM customers WHERE phone = '$phone' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $cus = mysqli_fetch_assoc($result);
        echo json_encode(['status' => 'success', 'name' => $cus['full_name']]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
    exit;
}
if ($action === 'create') {
    $source  = mysqli_real_escape_string($conn, $_POST['source'] ?? '');
    $phone   = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $name    = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $city    = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $ward    = mysqli_real_escape_string($conn, $_POST['ward'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $goods   = mysqli_real_escape_string($conn, $_POST['goods'] ?? '');
    $cod     = (float)($_POST['cod'] ?? 0);
    $weight  = (float)($_POST['weight'] ?? 0);
    $now     = date('Y-m-d H:i:s');

    $cus_id = 0;
    $check_cus = mysqli_query($conn, "SELECT id FROM customers WHERE phone = '$phone' LIMIT 1");
    
    if ($check_cus && mysqli_num_rows($check_cus) > 0) {
        $cus_id = mysqli_fetch_assoc($check_cus)['id'];
    } else {
        $sql_cus = "INSERT INTO customers (full_name, phone, created_at) VALUES ('$name', '$phone', '$now')";
        if (!mysqli_query($conn, $sql_cus)) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi khởi tạo khách hàng mới.']);
            exit;
        }
        $cus_id = mysqli_insert_id($conn);
    }

    // Xử lý tạo Đơn hàng
    $dest = "$address, $ward, $city";
    $tracking = "ORD-" . date('Y') . "-" . rand(1000, 9999);
    $sla = date('Y-m-d 18:00:00', strtotime('+2 days'));

    $sql_order = "INSERT INTO orders (
                    tracking_code, customer_id, goods_type, note, 
                    source_text, dest_text, weight, length, width, height, 
                    shipping_fee, cod_amount, status, sla_deadline, 
                    created_at, updated_at, pod_status
                  ) VALUES (
                    '$tracking', $cus_id, '$goods', NULL, 
                    '$source', '$dest', $weight, 0, 0, 0, 
                    0, $cod, 'pending', '$sla', 
                    '$now', '$now', 'pending'
                  )";
    
    if (mysqli_query($conn, $sql_order)) {
        $new_id = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO order_logs (order_id, status, description, created_at) VALUES ($new_id, 'pending', 'Đơn hàng được tạo thành công trên hệ thống', '$now')");
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không thể lưu đơn hàng vào cơ sở dữ liệu.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Lệnh truy vấn không hợp lệ.']);
?>