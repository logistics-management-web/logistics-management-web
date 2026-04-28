<?php
require_once __DIR__ . "/../../../config/db.php";

function rateList($search = '', $status = '', $limit = 5, $offset = 0) {
    global $conn;
    connectDB();
    
    $whereClause = "WHERE 1=1 ";
    if($search != ""){
        $search = mysqli_real_escape_string($conn, $search);
        $whereClause .= " and (rates.code_rates like '%$search%' or rates.name like '%$search%')";
    }
    if($status != ""){
        $status = mysqli_real_escape_string($conn, $status);
        $whereClause .= " and rates.status = '$status'";
    }
    $sql = "SELECT id, code_rates, name, version, status, effective_date
            FROM rates 
            $whereClause 
            ORDER BY id DESC
            LIMIT $offset, $limit";
    $query = mysqli_query($conn, $sql);
    $result = array();
    if ($query) {
        while($row = mysqli_fetch_assoc($query)) {
            $result[] = $row;
        }
    }
    return $result;
}
function rateCount($search = '', $status = '') {
    global $conn;
    connectDB();
    $whereClause = "WHERE 1=1 ";
    if ($search != '') { $search = mysqli_real_escape_string($conn, $search); $whereClause .= " AND (code_rates LIKE '%$search%' OR name LIKE '%$search%') "; }
    if ($status != '') { $status = mysqli_real_escape_string($conn, $status); $whereClause .= " AND status = '$status' "; }

    $sql = "SELECT COUNT(*) as total FROM rates $whereClause";
    $query = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($query);
    return $row['total'];
}

function rateGetById($id) {
    global $conn;
    connectDB();
    
    $id = (int)$id;
    $sql = "SELECT id, code_rates, name, version, status, effective_date 
            FROM rates 
            WHERE id = $id";
    $query = mysqli_query($conn, $sql);
    if ($query) {
        return mysqli_fetch_assoc($query);
    }
    return null;
}
function rateAdd($data) {
    global $conn;
    connectDB();
    
    $code_rates = mysqli_real_escape_string($conn, $data['code_rates']); // Thêm dòng này
    $name = mysqli_real_escape_string($conn, $data['name']);
    $version = mysqli_real_escape_string($conn, $data['version']);
    $effective_date = mysqli_real_escape_string($conn, $data['effective_date']);
    $status = mysqli_real_escape_string($conn, $data['status']); 

    // Thêm code_rates vào câu lệnh INSERT
    $sql = "INSERT INTO rates (code_rates, name, version, status, effective_date, created_at, updated_at) 
            VALUES ('$code_rates', '$name', '$version', '$status', '$effective_date', NOW(), NOW())";
    
    return mysqli_query($conn, $sql);
}

function rateEdit($data) {
    global $conn;
    connectDB();
    
    $id = (int)$data['id'];
    $code_rates = mysqli_real_escape_string($conn, $data['code_rates']); // Thêm dòng này
    $name = mysqli_real_escape_string($conn, $data['name']);
    $version = mysqli_real_escape_string($conn, $data['version']);
    $effective_date = mysqli_real_escape_string($conn, $data['effective_date']);
    $status = mysqli_real_escape_string($conn, $data['status']);

    $sql = "UPDATE rates SET 
                code_rates = '$code_rates', 
                name = '$name', 
                version = '$version', 
                status = '$status', 
                effective_date = '$effective_date', 
                updated_at = NOW() 
            WHERE id = $id";
            
    return mysqli_query($conn, $sql);
}

function rateDelete($data) {
    global $conn;
    connectDB();
    
    $id = (int)$data['delete_id'];
    $sql = "DELETE FROM rates WHERE id = $id";
            
    return mysqli_query($conn, $sql);
}
?>