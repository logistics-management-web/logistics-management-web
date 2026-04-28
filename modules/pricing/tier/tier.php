<?php
require_once "../../../config/db.php";
function tierList($route_id) {
    global $conn;
    connectDB();
    $sql = "SELECT * FROM rate_tiers WHERE rate_route_id = $route_id ORDER BY from_weight ASC";
    $query = mysqli_query($conn, $sql);
    $result = array();
    while($row = mysqli_fetch_assoc($query)) {
        $result[] = $row;
    }
    return $result;
}
function tierListByRouteIds($route_ids) {
    global $conn; // Biến kết nối mysqli của bạn
    
    if (empty($route_ids)) {
        return [];
    }

    // 1. Tạo chuỗi dấu ? (VD: ?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($route_ids), '?'));
    
    // Tạo chuỗi kiểu dữ liệu (VD: 'iii' cho 3 số nguyên)
    $types = str_repeat('i', count($route_ids)); 

    // 2. Chuẩn bị và thực thi truy vấn
    $sql = "SELECT * FROM rate_tiers WHERE rate_route_id IN ($placeholders) ORDER BY from_weight ASC";
    $stmt = $conn->prepare($sql);
    
    // Bind parameters động cho MySQLi (Sử dụng toán tử giải nén ... mảng)
    $stmt->bind_param($types, ...$route_ids);
    $stmt->execute();
    
    // LỖI CŨ Ở ĐÂY: Thay vì fetchAll() của PDO, dùng get_result() và fetch_all() của MySQLi
    $result = $stmt->get_result();
    $all_tiers = $result->fetch_all(MYSQLI_ASSOC);

    // 3. Gom nhóm dữ liệu bằng PHP
    $grouped_tiers = [];
    foreach ($all_tiers as $tier) {
        $route_id = $tier['rate_route_id'];
        $grouped_tiers[$route_id][] = $tier;
    }

    return $grouped_tiers;
}
function tierAdd($data) {
    global $conn;
    connectDB();
    $route_id = (int)$data['rate_route_id'];
    
    // Tìm bậc giá cuối cùng của tuyến đường này
    $sqlLastTier = "SELECT * FROM rate_tiers WHERE rate_route_id = $route_id ORDER BY from_weight DESC LIMIT 1";
    $lastTierQuery = mysqli_query($conn, $sqlLastTier);
    $lastTier = mysqli_fetch_assoc($lastTierQuery);
    
    if ($lastTier) {
        // Nếu đã có bậc giá, khối lượng bắt đầu phải bằng kết thúc của bậc trước
        if (empty($lastTier['to_weight'])) {
            // Nếu bậc trước đã là Max (để trống) thì không thể thêm bậc mới
            return false; 
        }
        $from = (float)$lastTier['to_weight'];
    } else {
        // Nếu là bậc đầu tiên, mặc định bắt đầu từ 0
        $from = 0.0;
    }

    $to = !empty($data['to_weight']) ? (float)$data['to_weight'] : "NULL";
    $step_w = (float)$data['step_weight'];
    $step_p = (float)$data['step_price'];

    // Biến $from đã được tự động gán thay vì lấy từ $data['from_weight']
    $sql = "INSERT INTO rate_tiers (rate_route_id, from_weight, to_weight, step_weight, step_price) 
            VALUES ($route_id, $from, $to, $step_w, $step_p)";
    return mysqli_query($conn, $sql);
}

function tierEdit($data) {
    global $conn;
    connectDB();
    $id = (int)$data['tier_id'];
    
    // Khối lượng "Đến" có thể để trống (Max)
    $to = !empty($data['to_weight']) ? (float)$data['to_weight'] : "NULL";
    $step_w = (float)$data['step_weight'];
    $step_p = (float)$data['step_price'];

    $sql = "UPDATE rate_tiers SET 
            to_weight = $to, 
            step_weight = $step_w, 
            step_price = $step_p 
            WHERE id = $id";
            
    return mysqli_query($conn, $sql);
}

function tierDelete($data) {
    global $conn;
    connectDB();
    $id = (int)$data['delete_tier_id'];
    
    $sql = "DELETE FROM rate_tiers WHERE id = $id";
    return mysqli_query($conn, $sql);
}