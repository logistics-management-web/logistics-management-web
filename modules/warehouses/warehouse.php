<?php
require "../../config/db.php";
function warehouseList() {
    global $conn;
    connectDB();
    $sql = "SELECT hubs.id, code, name, type, region, address, open_time, close_time, max_capacity, hubs.manager_id, hubs.is_active, full_name 
            FROM hubs
            INNER JOIN users ON hubs.manager_id = users.id";
    $query = mysqli_query($conn, $sql);
    $result = array();
    while($row = mysqli_fetch_assoc($query)) {
        $result[] = $row;
    }
    return $result;
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