<?php
require "../../config/db.php";

function calculateShippingFee($order_id) {
    global $conn;   
    connectDB();
    $safe_order_id = (int)$order_id;
    // 1. LẤY THÔNG TIN ĐƠN HÀNG
    $sqlOrder = "SELECT source_text, dest_text, weight FROM orders WHERE id = $safe_order_id";
    $orderQuery = mysqli_query($conn, $sqlOrder);
    $order = mysqli_fetch_assoc($orderQuery);
    
    if (!$order) {
        return ['error' => 'Không tìm thấy thông tin đơn hàng số: ' . $safe_order_id];
    }

    // Hàm nội bộ để làm sạch tên tỉnh/thành phố
    $getProvinceOnly = function($address) {
        $parts = explode(',', $address);
        $province = trim(end($parts));
        // Loại bỏ các tiền tố như Kho, Hub Tổng
        $province = preg_replace('/^(Kho|Hub Tổng)\s+/ui', '', $province);
        return $province;
    };

    $source_province = $getProvinceOnly($order['source_text']);
    $dest_province = $getProvinceOnly($order['dest_text']);
    
    $source_esc = mysqli_real_escape_string($conn, $source_province);
    $dest_esc = mysqli_real_escape_string($conn, $dest_province);
    $actual_weight = (float)$order['weight'];

    // 2. TÌM BẢNG GIÁ & TUYẾN ĐƯỜNG PHÙ HỢP
    $sqlRoute = "SELECT rr.id as route_id, rr.base_weight, rr.base_price, r.name as rate_name
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

    if (!$route) {
        return ['error' => 'Không tìm thấy bảng giá active cho tuyến: <strong>' . htmlspecialchars($source_province) . ' ➔ ' . htmlspecialchars($dest_province) . '</strong>'];
    }

    // 3. TÍNH TOÁN CƯỚC PHÍ
    $route_id = $route['route_id'];
    $base_weight = (float)$route['base_weight'];
    $base_price = (float)$route['base_price'];
    
    $total_extra_fee = 0;
    $total_fee = $base_price; // Mặc định tổng phí ít nhất là giá cơ bản
    $breakdown = "";

    // So sánh khối lượng
    if ($actual_weight <= $base_weight) {
        $breakdown = "Gói hàng ({$actual_weight}kg) nằm trong khối lượng cơ bản ({$base_weight}kg). Chỉ thu cước cơ bản.";
    } else {
        $extra_weight = $actual_weight - $base_weight;
        $breakdown = "Cước cơ bản ({$base_weight}kg): " . number_format($base_price) . " VNĐ.<br>";
        $breakdown .= "Khối lượng vượt: <strong>{$extra_weight}kg</strong>. Chi tiết phụ phí lũy tiến:<br>";
        
        $sqlTiers = "SELECT * FROM rate_tiers WHERE rate_route_id = $route_id ORDER BY from_weight ASC";
        $tiersQuery = mysqli_query($conn, $sqlTiers);
        
        if (mysqli_num_rows($tiersQuery) == 0) {
            $breakdown .= "<span class='text-danger'>Cảnh báo: Tuyến này chưa được cấu hình Bậc giá vượt mức!</span>";
        } else {
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
                    $tier_fee = $steps * $step_price;
                    
                    $total_extra_fee += $tier_fee;
                    $remaining_weight -= $weight_in_tier; 
                    
                    $tier_to_text = ($to === INF) ? 'Max' : $to.'kg';
                    $breakdown .= "- Bậc {$from}kg ➔ {$tier_to_text} (tính cho {$weight_in_tier}kg): {$steps} bước nhảy x " . number_format($step_price) . " = <strong>" . number_format($tier_fee) . " VNĐ</strong>.<br>";
                }
            }

            if ($remaining_weight > 0) {
                 $breakdown .= "<span class='text-danger'>Cảnh báo: Cấu hình bậc giá chưa đến Max. Gói hàng bị dư {$remaining_weight}kg không được tính phụ phí!</span><br>";
            }
            
            $total_fee = $base_price + $total_extra_fee;
            $breakdown .= "<hr><strong>Tổng phụ phí: " . number_format($total_extra_fee) . " VNĐ</strong>";
        }
    }

    return $total_fee;
}
?>