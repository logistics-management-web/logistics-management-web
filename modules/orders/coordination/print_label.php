<?php
session_start();
require_once "../../../config/config.php";
connectDB();
global $conn;

if (!isset($_SESSION["loggedin"]) || !isset($_GET['id'])) {
    die("Truy cập bị từ chối.");
}

$order_id = (int)$_GET['id'];

$sql = "SELECT 
            o.tracking_code, 
            CONCAT(h.name, ' - ', h.address) AS source_text,
            o.dest_text, 
            o.weight, 
            o.cod_amount 
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Tem - <?= htmlspecialchars($order['tracking_code']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        /* ================= SETUP TRANG IN ================= */
        @page { 
            size: 100mm 150mm; 
            margin: 0; 
        }
        * { box-sizing: border-box; }
        body { 
            font-family: 'Arial', sans-serif; /* Font cơ bản, dễ đọc nhất cho máy in */
            margin: 0; 
            padding: 0; 
            color: #000; 
            background: #fff;
            display: flex;
            justify-content: center;
        }

        /* ================= LAYOUT CHÍNH CỦA TEM ================= */
        .label-wrapper { 
            width: 100mm; 
            height: 150mm; 
            border: 2px solid #000; /* Viền ngoài cùng đậm */
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #fff;
        }

        /* ================= CÁC SECTION (KHỐI) ================= */
        .section {
            border-bottom: 2px solid #000;
            padding: 8px 12px;
        }
        .section:last-child {
            border-bottom: none;
        }

        /* Khối Header: Thương hiệu & Ngày in */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #000;
            color: #fff;
            padding: 10px 12px;
        }
        .brand-name {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 1px;
            margin: 0;
        }
        .print-date {
            font-size: 12px;
            font-weight: bold;
        }

        /* Khối Mã vạch */
        .barcode-section {
            text-align: center;
            padding: 15px 10px 5px 10px;
        }
        .barcode-container svg {
            max-width: 100%;
            height: 75px;
        }

        /* Khối Địa chỉ (Gửi & Nhận) */
        .address-grid {
            display: flex;
            flex-direction: column;
            padding: 0; /* Xóa padding vì đã có trong phần tử con */
        }
        .address-box {
            padding: 10px 12px;
        }
        .address-box.sender {
            border-bottom: 1px dashed #000;
        }
        .box-title {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }
        .box-content {
            font-size: 14px;
            line-height: 1.4;
        }
        .receiver-highlight {
            font-size: 18px;
            font-weight: bold;
            display: block;
            margin-top: 5px;
        }

        /* Khối Thông tin phụ (Khối lượng, Loại hàng...) */
        .info-grid {
            display: flex;
            border-bottom: 2px solid #000;
        }
        .info-col {
            flex: 1;
            padding: 10px 12px;
            border-right: 2px solid #000;
        }
        .info-col:last-child {
            border-right: none;
        }
        .info-value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }

        /* Khối Thu hộ (COD) */
        .cod-section {
            flex-grow: 1; /* Chiếm hết phần không gian còn lại ở dưới cùng */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        .cod-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .cod-amount {
            font-size: 32px;
            font-weight: 900;
            border: 4px solid #000;
            padding: 10px 20px;
            border-radius: 8px;
        }

        /* Nút thao tác trước khi in */
        .control-panel {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-print {
            padding: 12px 24px;
            background: #4318ff;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-print:hover { background: #3211c4; }

        @media print { 
            .no-print { display: none !important; } 
            body { 
                background: transparent; 
                display: block; 
            }
            
            /* Bỏ dòng border: none đi để giữ lại khung khi in */
            .label-wrapper {
                border: 2px solid #000 !important; 
            }

            /* Mẹo nhỏ: Ép trình duyệt in cả màu nền đen của thẻ Header, 
               nếu không trình duyệt sẽ tự động bỏ màu nền để tiết kiệm mực */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <div class="control-panel no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Tiến hành in tem
        </button>
    </div>

    <div class="label-wrapper">
        
        <div class="header-section">
            <h1 class="brand-name">LOGISCORE</h1>
            <span class="print-date"><?= date('d/m/Y H:i') ?></span>
        </div>

        <div class="section barcode-section">
            <div class="barcode-container">
                <svg id="real-barcode"></svg>
            </div>
        </div>

        <div class="section address-grid">
            <div class="address-box sender">
                <div class="box-title">Từ (Người gửi):</div>
                <div class="box-content"><?= htmlspecialchars($order['source_text'] ?? 'Chưa xác định') ?></div>
            </div>
            <div class="address-box receiver">
                <div class="box-title">Đến (Người nhận):</div>
                <div class="box-content receiver-highlight">
                    <?= htmlspecialchars($order['dest_text'] ?? 'Chưa xác định') ?>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-col">
                <div class="box-title">Khối lượng</div>
                <div class="info-value"><?= number_format((float)$order['weight'], 2) ?> kg</div>
            </div>
            <div class="info-col">
                <div class="box-title">Lưu ý giao hàng</div>
                <div class="info-value" style="font-size: 13px;">Gọi điện trước khi giao</div>
            </div>
        </div>

        <div class="cod-section">
            <div class="cod-title">TIỀN THU HỘ (COD)</div>
            <?php if ($order['cod_amount'] > 0): ?>
                <div class="cod-amount"><?= number_format($order['cod_amount']) ?> VNĐ</div>
            <?php else: ?>
                <div class="cod-amount">0 VNĐ</div>
                <div style="margin-top: 5px; font-weight: bold; font-size: 14px;">(KHÔNG THU TIỀN)</div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const trackingCode = "<?= htmlspecialchars($order['tracking_code']) ?>";
            
            JsBarcode("#real-barcode", trackingCode, {
                format: "CODE128",
                lineColor: "#000",
                width: 3,          // Tăng độ rộng vạch cho rõ nét
                height: 80,        // Tăng chiều cao
                displayValue: true,
                fontSize: 22,      // Chữ số to, dễ đọc
                fontOptions: "bold",
                textMargin: 8,
                margin: 0
            });
        });
    </script>
</body>
</html>