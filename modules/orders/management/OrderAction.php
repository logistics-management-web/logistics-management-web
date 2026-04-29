<?php
require_once '../../../config/config.php';

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
    $hub_id = (int)($_POST['hub_id'] ?? 0);
    $dest   = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $w      = (float)($_POST['weight'] ?? 0);

    if (!$hub_id || !$dest || $w <= 0) die(json_encode(['fee' => 0]));

    $sql_h = "SELECT address FROM hubs WHERE id = $hub_id LIMIT 1";
    $kq_h = mysqli_query($conn, $sql_h);
    $h = mysqli_fetch_assoc($kq_h);
    $arr = explode(',', $h['address']);
    $src = trim(end($arr)); // Ví dụ: "TP.HCM"

    
    $sql1 = "SELECT rr.id, rr.base_weight, rr.base_price 
             FROM rate_routes rr JOIN rates r ON rr.rate_id = r.id 
             WHERE r.status = 'active' 
             AND JSON_CONTAINS(rr.source_regions, '\"$src\"') 
             AND JSON_CONTAINS(rr.dest_regions, '\"$dest\"') LIMIT 1";
    $kq1 = mysqli_query($conn, $sql1);
    if (!$kq1 || mysqli_num_rows($kq1) == 0) die(json_encode(['fee' => 0]));

    $bg = mysqli_fetch_assoc($kq1);
    $fee = (float)$bg['base_price'];
    $kg_du = $w - (float)$bg['base_weight'];

    if ($kg_du > 0) {
        $id_tuyen = $bg['id'];
        $sql2 = "SELECT step_weight, step_price FROM rate_steps 
                 WHERE rate_route_id = $id_tuyen 
                 AND $w > from_weight AND (to_weight IS NULL OR $w <= to_weight) LIMIT 1";
        $kq2 = mysqli_query($conn, $sql2);
        if ($kq2 && mysqli_num_rows($kq2) > 0) {
            $buoc = mysqli_fetch_assoc($kq2);
            $fee += ceil($kg_du / (float)$buoc['step_weight']) * (float)$buoc['step_price'];
        }
    }
    echo json_encode(['fee' => $fee]);
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
    if ($id_kho > 0) {
        $kq_kho = mysqli_query($conn, "SELECT name FROM hubs WHERE id = $id_kho LIMIT 1");
        if ($kq_kho && mysqli_num_rows($kq_kho) > 0) {
            $ten_kho = mysqli_fetch_assoc($kq_kho)['name'];
        }
    }

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
                    source_text, dest_text, weight, length, width, height, 
                    shipping_fee, cod_amount, status, sla_deadline, 
                    created_at, updated_at, pod_status, hub_id
                  ) VALUES (
                    '$ma_don', $id_kh, '$hang', NULL, 
                    '$ten_kho', '$dc_nhan', $kg, 0, 0, 0, 
                    $cuoc, $cod, 'pending', '$han_sla', 
                    '$gio', '$gio', 'pending', $id_kho
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

echo json_encode(['status' => 'error', 'message' => 'Lệnh truy vấn không hợp lệ.']);
?>