<?php
require_once __DIR__ . "/../../../config/config.php";
function routeList($rate_id) {
    global $conn;
    connectDB();
    $sql = "SELECT * FROM rate_routes WHERE rate_id = $rate_id";
    $query = mysqli_query($conn, $sql);
    $result = array();
    while($row = mysqli_fetch_assoc($query)) {
        $result[] = $row;
    }
    return $result;
}

function routeAdd($data) {
    global $conn;
    connectDB();
    $rate_id = (int)$data['rate_id'];
    
    // Tách chuỗi người dùng nhập (cách nhau bằng dấu phẩy) thành Mảng (Array)
    // Ví dụ: "Hà Nội, Hải Phòng" -> ['Hà Nội', 'Hải Phòng']
    $source_arr = array_map('trim', explode(',', $data['source_regions']));
    $dest_arr = array_map('trim', explode(',', $data['dest_regions']));

    // Chuyển Mảng thành chuỗi JSON chuẩn (giữ nguyên tiếng Việt với JSON_UNESCAPED_UNICODE)
    $source_json = json_encode($source_arr, JSON_UNESCAPED_UNICODE);
    $dest_json = json_encode($dest_arr, JSON_UNESCAPED_UNICODE);

    // Escape chuỗi JSON trước khi đưa vào câu lệnh SQL
    $source = mysqli_real_escape_string($conn, $source_json);
    $dest = mysqli_real_escape_string($conn, $dest_json);

    $base_weight = (float)$data['base_weight'];
    $base_price = (float)$data['base_price'];

    $sql = "INSERT INTO rate_routes (rate_id, source_regions, dest_regions, base_weight, base_price) 
            VALUES ($rate_id, '$source', '$dest', $base_weight, $base_price)";
            
    return mysqli_query($conn, $sql);
}

function routeEdit($data) {
    global $conn;
    connectDB();
    $id = (int)$data['route_id'];
    
    $source_arr = array_map('trim', explode(',', $data['source_regions']));
    $dest_arr = array_map('trim', explode(',', $data['dest_regions']));

    $source_json = json_encode($source_arr, JSON_UNESCAPED_UNICODE);
    $dest_json = json_encode($dest_arr, JSON_UNESCAPED_UNICODE);

    $source = mysqli_real_escape_string($conn, $source_json);
    $dest = mysqli_real_escape_string($conn, $dest_json);
    $base_weight = (float)$data['base_weight'];
    $base_price = (float)$data['base_price'];

    $sql = "UPDATE rate_routes SET 
            source_regions = '$source', 
            dest_regions = '$dest', 
            base_weight = $base_weight, 
            base_price = $base_price 
            WHERE id = $id";
            
    return mysqli_query($conn, $sql);
}

function routeDelete($data) {
    global $conn;
    connectDB();
    $id = (int)$data['delete_route_id'];
    
    // Xóa các bậc giá liên quan trước để tránh rác dữ liệu
    mysqli_query($conn, "DELETE FROM rate_tiers WHERE rate_route_id = $id");
    
    $sql = "DELETE FROM rate_routes WHERE id = $id";
    return mysqli_query($conn, $sql);
}