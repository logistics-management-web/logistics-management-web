<?php
require "../../config/config.php";

function driverList() {
    global $conn;
    connectDB();
    // Thêm driver_code vào SELECT
    $sql = "SELECT id, driver_code, full_name, phone, license_class, work_status, created_at FROM drivers";
    $query = mysqli_query($conn, $sql);
    $result = array();
    if ($query) {
        while($row = mysqli_fetch_assoc($query)) {
            $result[] = $row;
        }
    }
    return $result;
}

function driverAdd($data) {
    global $conn;
    connectDB();
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $currentTime = date('Y-m-d H:i:s');
    
    // Nhận driver_code từ form
    $driver_code = mysqli_real_escape_string($conn, $data['driver_code']);
    $full_name = mysqli_real_escape_string($conn, $data['full_name']);
    $phone = mysqli_real_escape_string($conn, $data['phone']);
    $license_class = mysqli_real_escape_string($conn, $data['license_class']);
    $work_status = 'active'; 

    $sql = "INSERT INTO drivers (driver_code, full_name, phone, license_class, work_status, created_at) 
            VALUES ('$driver_code', '$full_name', '$phone', '$license_class', '$work_status', '$currentTime')";
    
    return mysqli_query($conn, $sql);
}

function driverEdit($data) {
    global $conn;
    connectDB();
    $id = (int)$data['id'];
    $driver_code = mysqli_real_escape_string($conn, $data['driver_code']);
    $full_name = mysqli_real_escape_string($conn, $data['full_name']);
    $phone = mysqli_real_escape_string($conn, $data['phone']);
    $license_class = mysqli_real_escape_string($conn, $data['license_class']);
    
    $sql = "UPDATE drivers SET 
                driver_code = '$driver_code',
                full_name = '$full_name', 
                phone = '$phone', 
                license_class = '$license_class'
            WHERE id = $id";
            
    return mysqli_query($conn, $sql);
}

function driverToggleStatus($data) {
    global $conn;
    connectDB();
    $id = (int)$data['toggle_id'];
    $current_status = $data['current_status'];
    $new_status = ($current_status === 'active') ? 'offline' : 'active';
    $sql = "UPDATE drivers SET work_status = '$new_status' WHERE id = $id";
    return mysqli_query($conn, $sql);
}
function driverDelete($id) {
    global $conn;
    connectDB();

    $id = (int)$id;
    $sql = "DELETE FROM drivers WHERE id = $id";

    return mysqli_query($conn, $sql);
}
?>