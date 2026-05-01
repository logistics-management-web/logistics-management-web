<?php
function calculateShippingFee($source_text, $dest_text, $weight) {
    global $conn;   
    
    // 1. LÀM SẠCH VÀ LẤY TÊN TỈNH/THÀNH PHỐ
    $getProvinceOnly = function($address) {
        $parts = explode(',', $address);
        $province = trim(end($parts));
        return preg_replace('/^(Kho|Hub Tổng|Trạm|Hub)\s+/ui', '', $province);
    };

    $src = mysqli_real_escape_string($conn, $getProvinceOnly($source_text));
    $dest = mysqli_real_escape_string($conn, trim($dest_text)); // Thêm trim cho an toàn
    $actual_weight = (float)$weight;

    // 2. TÌM TUYẾN ĐƯỜNG (Dùng lệnh LIKE để bao trọn mọi lỗi nhận diện chuỗi)
    $sqlRoute = "SELECT rr.id as route_id, rr.base_weight, rr.base_price
                 FROM rate_routes rr
                 JOIN rates r ON rr.rate_id = r.id
                 WHERE r.status = 'active' 
                 AND (
                    (rr.source_regions LIKE '%$src%' AND rr.dest_regions LIKE '%$dest%')
                    OR
                    (rr.source_regions LIKE '%$dest%' AND rr.dest_regions LIKE '%$src%')
                    OR
                    (rr.source_regions LIKE '%Toàn quốc%' AND rr.dest_regions LIKE '%Toàn quốc%')
                 ) 
                 ORDER BY 
                    CASE WHEN rr.source_regions LIKE '%Toàn quốc%' THEN 2 ELSE 1 END
                 LIMIT 1";
                 
    $routeQuery = mysqli_query($conn, $sqlRoute);
    $route = $routeQuery ? mysqli_fetch_assoc($routeQuery) : null;

    if (!$route) {
        return false; 
    }

    $route_id = $route['route_id'];
    $base_weight = (float)$route['base_weight'];
    $base_price = (float)$route['base_price'];
    
    $total_fee = $base_price; 

    // 3. TÍNH PHỤ PHÍ LŨY TIẾN THEO TẦNG
    if ($actual_weight > $base_weight) {
        $extra_weight = $actual_weight - $base_weight;
        $total_extra_fee = 0;
        
        $sqlTiers = "SELECT from_weight, to_weight, step_weight, step_price 
                     FROM rate_tiers 
                     WHERE rate_route_id = $route_id 
                     ORDER BY from_weight ASC";
        $tiersQuery = mysqli_query($conn, $sqlTiers);
        
        if ($tiersQuery && mysqli_num_rows($tiersQuery) > 0) {
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
                    $steps = ceil($weight_in_tier / $step_weight);
                    $total_extra_fee += ($steps * $step_price);
                    $remaining_weight -= $weight_in_tier; 
                }
            }
            $total_fee += $total_extra_fee;
        }
    }

    return $total_fee;
}
?>