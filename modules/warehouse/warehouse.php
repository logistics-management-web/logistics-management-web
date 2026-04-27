<?php
require "../../config/db.php";
// Sửa hàm warehouseList
function warehouseList($search = '', $type = '', $status = '', $region = '', $manager_id = '', $limit = 10, $offset = 0) {
    global $conn;
    connectDB();

    $whereClause = "WHERE 1=1 ";
    if ($search != '') {
        $search = mysqli_real_escape_string($conn, $search);
        $whereClause .= " AND (hubs.code LIKE '%$search%' OR hubs.name LIKE '%$search%') ";
    }
    if ($type != '') {
        $type = mysqli_real_escape_string($conn, $type);
        $whereClause .= " AND hubs.type = '$type' ";
    }
    if ($status != '') {
        $status = (int)$status;
        $whereClause .= " AND hubs.is_active = $status ";
    }
    // Thêm lọc theo khu vực
    if ($region != '') {
        $region = mysqli_real_escape_string($conn, $region);
        $whereClause .= " AND hubs.region LIKE '%$region%' ";
    }
    // Thêm lọc theo quản lý
    if ($manager_id != '') {
        $manager_id = (int)$manager_id;
        $whereClause .= " AND hubs.manager_id = $manager_id ";
    }

    $sql = "SELECT hubs.*, users.full_name 
            FROM hubs
            LEFT JOIN users ON hubs.manager_id = users.id
            $whereClause
            ORDER BY hubs.created_at DESC
            LIMIT $limit OFFSET $offset";
            
    $query = mysqli_query($conn, $sql);
    $result = array();
    while($row = mysqli_fetch_assoc($query)) { $result[] = $row; }
    return $result;
}

function warehouseCount($search = '', $type = '', $status = '', $region = '', $manager_id = '') {
    global $conn;
    connectDB();
    $whereClause = "WHERE 1=1 ";
    if ($search != '') { $search = mysqli_real_escape_string($conn, $search); $whereClause .= " AND (code LIKE '%$search%' OR name LIKE '%$search%') "; }
    if ($type != '') { $type = mysqli_real_escape_string($conn, $type); $whereClause .= " AND type = '$type' "; }
    if ($status != '') { $status = (int)$status; $whereClause .= " AND is_active = $status "; }
    if ($region != '') { $region = mysqli_real_escape_string($conn, $region); $whereClause .= " AND region LIKE '%$region%' "; }
    if ($manager_id != '') { $manager_id = (int)$manager_id; $whereClause .= " AND manager_id = $manager_id "; }

    $sql = "SELECT COUNT(*) as total FROM hubs $whereClause";
    $query = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($query);
    return $row['total'];
}
function warehouseAdd($data) {
    global $conn;
    connectDB();
    // Lọc dữ liệu đầu vào để tránh SQL Injection
    $code = mysqli_real_escape_string($conn, $data['code']);
    $name = mysqli_real_escape_string($conn, $data['name']);
    $type = mysqli_real_escape_string($conn, $data['type']);
    $region = mysqli_real_escape_string($conn, $data['region']);
    $address = mysqli_real_escape_string($conn, $data['address']);
    $manager_id = (int)$data['manager_id'];
    $open_time = mysqli_real_escape_string($conn, $data['open_time']);
    $close_time = mysqli_real_escape_string($conn, $data['close_time']);
    $max_capacity = (int)$data['max_capacity'];
    
    // Mặc định kho mới tạo sẽ ở trạng thái hoạt động (1)
    $is_active = 1; 

    $sql = "INSERT INTO hubs (code, name, type, region, address, manager_id, open_time, close_time, max_capacity, is_active, created_at, updated_at) 
            VALUES ('$code', '$name', '$type', '$region', '$address', $manager_id, '$open_time', '$close_time', $max_capacity, $is_active, NOW(), NOW())";
    
    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        return false;
    }
}
function ManagerHubList(){
    global $conn;
    connectDB();
    $sql = "select id, full_name from users where role = 'hub_manager'";
    $query = mysqli_query($conn, $sql);
    $result = array();
    while($row = mysqli_fetch_assoc($query)){
        $result[] = $row;
    }
    return $result;
}

function warehouseEdit($data) {
    global $conn;
    connectDB();
    
    $id = (int)$data['id'];
    $code = mysqli_real_escape_string($conn, $data['code']);
    $name = mysqli_real_escape_string($conn, $data['name']);
    $type = mysqli_real_escape_string($conn, $data['type']);
    $region = mysqli_real_escape_string($conn, $data['region']);
    $address = mysqli_real_escape_string($conn, $data['address']);
    $manager_id = (int)$data['manager_id'];
    $open_time = mysqli_real_escape_string($conn, $data['open_time']);
    $close_time = mysqli_real_escape_string($conn, $data['close_time']);
    $max_capacity = (int)$data['max_capacity'];
    
    $sql = "UPDATE hubs SET 
                code = '$code', 
                name = '$name', 
                type = '$type', 
                region = '$region', 
                address = '$address', 
                manager_id = $manager_id, 
                open_time = '$open_time', 
                close_time = '$close_time', 
                max_capacity = $max_capacity, 
                updated_at = NOW() 
            WHERE id = $id";
            
    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        return false;
    }
}

function warehouseToggleStatus($data) {
    global $conn;
    connectDB();
    
    $id = (int)$data['toggle_id'];
    $current_status = (int)$data['current_status'];
    
    $new_status = $current_status === 1 ? 0 : 1;
    
    $sql = "UPDATE hubs SET is_active = $new_status, updated_at = NOW() WHERE id = $id";
            
    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        return false;
    }
}

function warehouseDelete($data) {
    global $conn;
    connectDB();
    $id = (int)$data['delete_id'];
    $sql = "DELETE FROM hubs WHERE id = $id";
    if (mysqli_query($conn, $sql)) return true;
    else return false;
}