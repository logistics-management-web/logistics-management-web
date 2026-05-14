<?php
session_start();
require_once "../../../config/config.php";
connectDB();
global $conn;

if (!isset($_SESSION["loggedin"]) || !isset($_GET['id'])) {
    die("Truy cập bị từ chối.");
}

$order_id = (int)$_GET['id'];

// Lấy thông tin đơn hàng
$sql = "SELECT o.tracking_code, h.name as source_name, o.dest_text, o.weight, o.cod_amount 
        FROM orders o 
        LEFT JOIN hubs h ON o.source_id = h.id 
        WHERE o.id = ?";
        
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
disconnectDB();

if (!$order) die("Không tìm thấy đơn hàng.");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Tem - <?= htmlspecialchars($order['tracking_code']) ?></title>
    <style>
        /* Thiết kế kích thước tem chuẩn (ví dụ 100x150mm) */
        @page { size: 100mm 150mm; margin: 0; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 15px; color: #000; }
        .label-container { border: 2px solid #000; padding: 15px; border-radius: 8px; width: 340px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .tracking { font-size: 24px; font-weight: bold; letter-spacing: 2px; }
        .info-block { margin-bottom: 15px; }
        .info-title { font-size: 12px; color: #555; text-transform: uppercase; }
        .info-content { font-size: 16px; font-weight: bold; }
        .cod-box { background-color: #000; color: #fff; text-align: center; padding: 10px; font-size: 20px; font-weight: bold; border-radius: 4px; }
        
        /* Cập nhật style cho khu vực chứa mã vạch */
        .barcode-container { margin-top: 20px; text-align: center; }
        
        /* Tự động kích hoạt hộp thoại in khi trang load xong */
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding:10px 20px; cursor:pointer;">Tiến hành In</button>
    </div>

    <div class="label-container">
        <div class="header">
            <h2>LOGISCORE</h2>
            <div class="tracking"><?= htmlspecialchars($order['tracking_code']) ?></div>
        </div>
        
        <div class="info-block">
            <div class="info-title">Người gửi (Từ)</div>
            <div class="info-content"><?= htmlspecialchars($order['source_name'] ?? 'Hệ thống MD Logistic') ?></div>
        </div>
        
        <div class="info-block">
            <div class="info-title">Người nhận (Đến)</div>
            <div class="info-content"><?= htmlspecialchars($order['dest_text']) ?></div>
        </div>
        
        <div class="info-block" style="display: flex; justify-content: space-between;">
            <div>
                <span class="info-title">Khối lượng:</span> <br>
                <span class="info-content"><?= htmlspecialchars($order['weight']) ?> kg</span>
            </div>
            <div>
                <span class="info-title">Ngày in:</span> <br>
                <span class="info-content"><?= date('d/m/Y') ?></span>
            </div>
        </div>

        <?php if ($order['cod_amount'] > 0): ?>
        <div class="cod-box">
            THU HỘ: <?= number_format($order['cod_amount']) ?> VNĐ
        </div>
        <?php endif; ?>

        <div class="barcode-container">
            <svg id="barcode"></svg>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    
    <script>
        // 2. Lấy tracking code từ PHP và truyền vào Javascript
        const trackingCode = "<?= htmlspecialchars($order['tracking_code']) ?>";

        // 3. Khởi tạo mã vạch
        JsBarcode("#barcode", trackingCode, {
            format: "CODE128",  // Chuẩn mã vạch dùng trong vận chuyển
            lineColor: "#000000",
            width: 2,           // Độ rộng của vạch
            height: 70,         // Chiều cao của vạch
            displayValue: true, // Hiển thị mã số bên dưới vạch
            fontSize: 18,
            margin: 0
        });

        // 4. Kích hoạt lệnh in sau khi trang và mã vạch đã tải xong
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>