<?php
require "../../config/config.php";

function truckList() {
global $conn;
    connectDB();
    // Đã sửa expire_date thành expiry_date theo đúng database của An
    $sql = "SELECT trucks.*, drivers.full_name AS driver_name,
                   truck_documents.document_type, 
                   truck_documents.document_number, 
                   truck_documents.issue_date, 
                   truck_documents.expiry_date
            FROM trucks 
            LEFT JOIN drivers ON trucks.main_driver_id = drivers.id
            LEFT JOIN truck_documents ON trucks.id = truck_documents.truck_id"; 
    $query = mysqli_query($conn, $sql);
    $result = array();
    while($row = mysqli_fetch_assoc($query)) {
        $result[] = $row;
    }
    return $result;
}

// Hàm này tương đương với ManagerHubList() bên kho bãi
function driverSelectionList() {
    global $conn;
    connectDB();
    $sql = "SELECT id, full_name FROM drivers WHERE work_status = 'active'";
    $query = mysqli_query($conn, $sql);
    $result = array();
    while($row = mysqli_fetch_assoc($query)){
        $result[] = $row;
    }
    return $result;
}

function truckAdd($data) {
  global $conn;
    connectDB();
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $currentTime = date('Y-m-d H:i:s');

    // --- Lấy dữ liệu xe ---
    $plate = mysqli_real_escape_string($conn, $data['plate_number']);
    $type = mysqli_real_escape_string($conn, $data['truck_type']);
    $capacity = (int)$data['capacity_kg'];
    $brand = mysqli_real_escape_string($conn, $data['brand_model']);
    $driver_id = (int)$data['main_driver_id'];

    // --- Lấy dữ liệu giấy tờ ---
    $doc_type = mysqli_real_escape_string($conn, $data['document_type']);
    $doc_num = mysqli_real_escape_string($conn, $data['document_number']);
    $issue_date = mysqli_real_escape_string($conn, $data['issue_date']);
    $expiry_date = mysqli_real_escape_string($conn, $data['expiry_date']);

    // Bước 1: Lưu vào bảng trucks
    $sql_truck = "INSERT INTO trucks (plate_number, truck_type, capacity_kg, brand_model, main_driver_id, status, created_at, updated_at) 
                  VALUES ('$plate', '$type', $capacity, '$brand', $driver_id, 'active', '$currentTime', '$currentTime')";
    
    if (mysqli_query($conn, $sql_truck)) {
        // Bước 2: Lấy ID của xe vừa tạo
        $new_truck_id = mysqli_insert_id($conn);

        // Bước 3: Lưu vào bảng truck_documents
        $sql_doc = "INSERT INTO truck_documents (truck_id, document_type, document_number, issue_date, expiry_date, created_at, updated_at) 
                    VALUES ($new_truck_id, '$doc_type', '$doc_num', '$issue_date', '$expiry_date', '$currentTime', '$currentTime')";
        
        return mysqli_query($conn, $sql_doc);
    }
    return false;
}

// 3. Chỉnh sửa xe tải
function truckEdit($data) {
    global $conn;
    connectDB();
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $currentTime = date('Y-m-d H:i:s');

    $id = (int)$data['id']; // ID của xe
    $plate = mysqli_real_escape_string($conn, $data['plate_number']);
    $type = mysqli_real_escape_string($conn, $data['truck_type']);
    $capacity = (int)$data['capacity_kg'];
    $brand = mysqli_real_escape_string($conn, $data['brand_model']);
    $driver_id = (int)$data['main_driver_id'];

    // Dữ liệu giấy tờ mới từ Form
    $doc_type = mysqli_real_escape_string($conn, $data['document_type']);
    $doc_num = mysqli_real_escape_string($conn, $data['document_number']);
    $issue_date = mysqli_real_escape_string($conn, $data['issue_date']);
    $expiry_date = mysqli_real_escape_string($conn, $data['expiry_date']);

    // Bước 1: Cập nhật bảng trucks
    $sql_truck = "UPDATE trucks SET 
                plate_number = '$plate', 
                truck_type = '$type', 
                capacity_kg = $capacity, 
                brand_model = '$brand',
                main_driver_id = $driver_id,
                updated_at = '$currentTime' 
            WHERE id = $id";
            
    if (mysqli_query($conn, $sql_truck)) {
        // Bước 2: Cập nhật bảng truck_documents (dựa vào truck_id)
        $sql_doc = "UPDATE truck_documents SET 
                    document_type = '$doc_type',
                    document_number = '$doc_num',
                    issue_date = '$issue_date',
                    expiry_date = '$expiry_date',
                    updated_at = '$currentTime'
                WHERE truck_id = $id";
        
        return mysqli_query($conn, $sql_doc);
    }
    return false;
}
function truckDeleteFull($id) {
    global $conn;
    connectDB();
    
    // Bắt đầu Transaction (Giao dịch an toàn)
    mysqli_begin_transaction($conn);

    try {
        // 1. Xóa các bản ghi trong nhật ký vận hành (operating_logs)
        $sql1 = "DELETE FROM truck_operating_logs WHERE truck_id = $id";
        mysqli_query($conn, $sql1);

        // 2. Xóa các giấy tờ liên quan (truck_documents)
        $sql2 = "DELETE FROM truck_documents WHERE truck_id = $id";
        mysqli_query($conn, $sql2);

        // 3. Cuối cùng mới xóa xe trong bảng chính (trucks)
        $sql3 = "DELETE FROM trucks WHERE id = $id";
        mysqli_query($conn, $sql3);

        // Nếu mọi thứ ổn, xác nhận thay đổi vào DB
        mysqli_commit($conn);
        return true;

    } catch (Exception $e) {
        // Nếu có lỗi, khôi phục lại dữ liệu ban đầu
        mysqli_rollback($conn);
        return false;
    }
}

function operatingLogList() {
    global $conn;
    connectDB();
    // JOIN với bảng xe để lấy biển số (plate_number)
    $sql = "SELECT truck_operating_logs.*, trucks.plate_number 
            FROM truck_operating_logs 
            LEFT JOIN trucks ON truck_operating_logs.truck_id = trucks.id 
            ORDER BY date_logged DESC";
    $query = mysqli_query($conn, $sql);
    $result = array();
    while($row = mysqli_fetch_assoc($query)) {
        $result[] = $row;
    }
    return $result;
}

function operatingLogAdd($data) {
    global $conn;
    connectDB();
    
    $truck_id = (int)$data['truck_id'];
    $event_type = mysqli_real_escape_string($conn, $data['event_type']);
    $description = mysqli_real_escape_string($conn, $data['description']);
    $cost = (float)$data['cost'];
    $date_logged = mysqli_real_escape_string($conn, $data['date_logged']);
    
    // --- XỬ LÝ UPLOAD ẢNH ---
    $receipt_url = ""; 
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0) {
        $target_dir = "uploads/";
        // Tạo thư mục nếu chưa có
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        // Đặt tên file duy nhất để không bị trùng (dùng hàm time)
        $file_name = time() . '_' . basename($_FILES["receipt_file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["receipt_file"]["tmp_name"], $target_file)) {
            $receipt_url = $target_file; // Lưu đường dẫn này vào DB
        }
    }

    $sql = "INSERT INTO truck_operating_logs (truck_id, event_type, description, cost, date_logged, receipt_url, created_at) 
            VALUES ($truck_id, '$event_type', '$description', $cost, '$date_logged', '$receipt_url', NOW())";
    
    return mysqli_query($conn, $sql);
}
function operatingLogEdit($data) {
    global $conn;
    connectDB();
    
    $id = (int)$data['id'];
    $truck_id = (int)$data['truck_id'];
    $driver_id = !empty($data['driver_id']) ? (int)$data['driver_id'] : "NULL";
    $event_type = mysqli_real_escape_string($conn, $data['event_type']);
    $description = mysqli_real_escape_string($conn, $data['description']);
    $cost = (float)$data['cost'];
    $date_logged = mysqli_real_escape_string($conn, $data['date_logged']);

    // --- XỬ LÝ ẢNH KHI SỬA ---
    $sql_update_img = "";
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . '_' . basename($_FILES["receipt_file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["receipt_file"]["tmp_name"], $target_file)) {
            // Nếu upload thành công, chuẩn bị đoạn SQL để cập nhật cột receipt_url
            $sql_update_img = ", receipt_url = '$target_file'";
        }
    }

    $sql = "UPDATE truck_operating_logs SET 
                truck_id = $truck_id, 
                driver_id = $driver_id,
                event_type = '$event_type', 
                description = '$description', 
                cost = $cost, 
                date_logged = '$date_logged'
                $sql_update_img 
            WHERE id = $id";
            
    return mysqli_query($conn, $sql);
}
function operatingLogDelete($data) {
    global $conn;
    connectDB();
    
    // Ép kiểu ID về số nguyên để bảo mật
    $id = (int)$data['delete_id'];
    
    // Câu lệnh xóa dòng dữ liệu dựa trên ID
    $sql = "DELETE FROM truck_operating_logs WHERE id = $id";
    
    return mysqli_query($conn, $sql);
}
// Trong file truck.php
function getTruckDocuments($truck_id) { //hàm để lấy thông tin từ bảng giấy tờ
    global $conn;
    connectDB();
    $id = (int)$truck_id;
    $sql = "SELECT * FROM truck_documents WHERE truck_id = $id";
    $query = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($query);
}

// 4. Bật/Tắt trạng thái xe tải
function truckToggleStatus($data) {
    global $conn;
    connectDB();
    $id = (int)$data['toggle_id'];
    $new_status = ($data['current_status'] === 'active') ? 'inactive' : 'active';
    
    $sql = "UPDATE trucks SET status = '$new_status', updated_at = NOW() WHERE id = $id";
    return mysqli_query($conn, $sql);
}

?>