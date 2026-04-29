<?php
require_once "../../config/config.php";

function calculateShippingFee($source_text, $dest_text, $weight) {
    global $conn;   
    connectDB();
    // 1. XỬ LÝ DỮ LIỆU ĐẦU VÀO
    // Hàm nội bộ để làm sạch tên tỉnh/thành phố
    $getProvinceOnly = function($address) {
        $parts = explode(',', $address);
        $province = trim(end($parts));
        return preg_replace('/^(Kho|Hub Tổng)\s+/ui', '', $province);
    };

    $source_province = $getProvinceOnly($source_text);
    $dest_province = $getProvinceOnly($dest_text);
    
    $source_esc = mysqli_real_escape_string($conn, $source_province);
    $dest_esc = mysqli_real_escape_string($conn, $dest_province);
    $actual_weight = (float)$weight;

    // 2. TÌM BẢNG GIÁ & TUYẾN ĐƯỜNG PHÙ HỢP
    $sqlRoute = "SELECT rr.id as route_id, rr.base_weight, rr.base_price
                 FROM rate_routes rr
                 JOIN rates r ON rr.rate_id = r.id
                 WHERE r.status = 'active' 
                 AND (
                    (JSON_SEARCH(rr.source_regions, 'one', CONCAT('%', '$source_esc', '%')) IS NOT NULL AND JSON_SEARCH(rr.dest_regions, 'one', CONCAT('%', '$dest_esc', '%')) IS NOT NULL)
                    OR
                    (JSON_SEARCH(rr.source_regions, 'one', CONCAT('%', '$dest_esc', '%')) IS NOT NULL AND JSON_SEARCH(rr.dest_regions, 'one', CONCAT('%', '$source_esc', '%')) IS NOT NULL)
                 ) 
                 LIMIT 1";
                 
    $routeQuery = mysqli_query($conn, $sqlRoute);
    $route = mysqli_fetch_assoc($routeQuery);

    // Trả về false (hoặc -1) nếu không tìm thấy tuyến đường, giúp bên ngoài dễ dàng dùng lệnh if để kiểm tra lỗi
    if (!$route) {
        return false; 
    }

    // 3. TÍNH TOÁN TỔNG SỐ TIỀN
    $route_id = $route['route_id'];
    $base_weight = (float)$route['base_weight'];
    $base_price = (float)$route['base_price'];
    
    $total_fee = $base_price; // Phí khởi điểm ít nhất bằng giá cơ bản

    // Nếu khối lượng thực tế lớn hơn khối lượng cơ bản thì mới tính thêm phụ phí
    if ($actual_weight > $base_weight) {
        $extra_weight = $actual_weight - $base_weight;
        $total_extra_fee = 0;
        
        $sqlTiers = "SELECT from_weight, to_weight, step_weight, step_price 
                     FROM rate_tiers 
                     WHERE rate_route_id = $route_id 
                     ORDER BY from_weight ASC";
        $tiersQuery = mysqli_query($conn, $sqlTiers);
        
        if (mysqli_num_rows($tiersQuery) > 0) {
            $remaining_weight = $extra_weight;
            
            while ($tier = mysqli_fetch_assoc($tiersQuery)) {
                if ($remaining_weight <= 0) break;

                $from = (float)$tier['from_weight'];
                $to = !empty($tier['to_weight']) ? (float)$tier['to_weight'] : INF; 
                $step_weight = (float)$tier['step_weight'];
                $step_price = (float)$tier['step_price'];
                
                $tier_capacity = $to - $from;
                $weight_in_tier = min($remaining_weight, $tier_capacity);
                
                if ($weight_in_tier > 0) {
                    // Tính số bước nhảy
                    $steps = ceil($weight_in_tier / $step_weight);
                    $total_extra_fee += ($steps * $step_price);
                    
                    // Trừ đi khối lượng đã tính phí
                    $remaining_weight -= $weight_in_tier; 
                }
            }
            // Cộng phụ phí lũy tiến vào tổng tiền
            $total_fee += $total_extra_fee;
        }
    }

    // 4. TRẢ VỀ TỔNG SỐ TIỀN CUỐI CÙNG
    return $total_fee;
}
?>