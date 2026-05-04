<?php
require_once '../../../config/config.php';
require_once '../../pricing/shippingFee.php';

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



if ($action === 'calc_fee') {
    $id_kho = (int)($_POST['hub_id'] ?? 0);
    $tinh_nhan = $_POST['city'] ?? '';
    $kg = (float)($_POST['weight'] ?? 0);

    // Nếu thiếu dữ liệu, trả về 0 luôn
    if (!$id_kho || !$tinh_nhan || $kg <= 0) {
        echo json_encode(['fee' => 0]);
        exit;
    }

    // Lấy chuỗi địa chỉ của Kho để làm $source_text
    $sql_h = "SELECT address, name FROM hubs WHERE id = $id_kho LIMIT 1";
    $kq_h = mysqli_query($conn, $sql_h);
    if ($kq_h && mysqli_num_rows($kq_h) > 0) {
        $kho = mysqli_fetch_assoc($kq_h);
        // Ưu tiên lấy address, nếu address rỗng thì lấy name
        $dc_kho = !empty($kho['address']) ? $kho['address'] : $kho['name'];
        
        // GỌI HÀM TỪ FILE CŨ CỦA BẠN (Không cần sửa file shippingFee.php)
        $phi_ship = calculateShippingFee($dc_kho, $tinh_nhan, $kg);
        
        echo json_encode(['fee' => $phi_ship ?: 0]);
    } else {
        echo json_encode(['fee' => 0]);
    }
    exit;
}


if ($action === 'create') {
    
    $id_kho = (int)($_POST['hub_id'] ?? 0);
    $sdt    = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $ten    = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $tinh   = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $xa     = mysqli_real_escape_string($conn, $_POST['ward'] ?? '');
    $dc     = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $hang   = mysqli_real_escape_string($conn, $_POST['goods'] ?? '');
    $cod    = (float)($_POST['cod'] ?? 0);
    $cuoc   = (float)($_POST['shipping_fee'] ?? 0);
    $kg     = (float)($_POST['weight'] ?? 0);
    $gio    = date('Y-m-d H:i:s');

    $ten_kho = "";
    $dc_kho = "";
    if ($id_kho > 0) {
        $kq_kho = mysqli_query($conn, "SELECT name, address FROM hubs WHERE id = $id_kho LIMIT 1");
        if ($kq_kho && mysqli_num_rows($kq_kho) > 0) {
            $tt_kho = mysqli_fetch_assoc($kq_kho);
            $ten_kho = $tt_kho['name'];
            $dc_kho  = !empty($tt_kho['address']) ? $tt_kho['address'] : $tt_kho['name'];
        }
    }

    // --- SỬ DỤNG HÀM GỐC TỪ SHIPPINGFEE.PHP ĐỂ TÍNH LẠI CƯỚC CHUẨN ---
    $cuoc_chuan = calculateShippingFee($dc_kho, $tinh, $kg);
    $cuoc_cuoi_cung = ($cuoc_chuan !== false) ? $cuoc_chuan : $cuoc;

    $id_kh = 0;
    $kq_kh = mysqli_query($conn, "SELECT id FROM customers WHERE phone = '$sdt' LIMIT 1");
    
    if ($kq_kh && mysqli_num_rows($kq_kh) > 0) {
        $id_kh = mysqli_fetch_assoc($kq_kh)['id'];
    } else {
        $sql_kh = "INSERT INTO customers (full_name, phone, created_at) VALUES ('$ten', '$sdt', '$gio')";
        if (!mysqli_query($conn, $sql_kh)) {
            die(json_encode(['status' => 'error', 'message' => 'Lỗi khởi tạo khách hàng mới.']));
        }
        $id_kh = mysqli_insert_id($conn);
    }

    $dc_nhan = "$dc, $xa, $tinh";
    $ma_don  = "ORD-" . date('Y') . "-" . rand(1000, 9999);
    $han_sla = date('Y-m-d 18:00:00', strtotime('+2 days'));

    $sql_don = "INSERT INTO orders (
                tracking_code, customer_id, goods_type, note, 
                source_id, dest_text, weight, length, width, height, 
                shipping_fee, cod_amount, status, sla_deadline, 
                created_at, updated_at, pod_status
              ) VALUES (
                '$ma_don', $id_kh, '$hang', NULL, 
                $id_kho, '$dc_nhan', $kg, 0, 0, 0, 
                $cuoc_cuoi_cung, $cod, 'pending', '$han_sla', 
                '$gio', '$gio', 'pending'
              )";
    
    if (mysqli_query($conn, $sql_don)) {
        $id_moi = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO order_logs (order_id, status, description, created_at) VALUES ($id_moi, 'pending', 'Đơn hàng được tạo thành công tại $ten_kho', '$gio')");
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không thể lưu đơn hàng vào CSDL.']);
    }
    exit;
}
?>