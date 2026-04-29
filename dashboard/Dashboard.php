<?php
error_reporting(0);
require_once '../config/config.php';
global $conn;
connectDB();
$bay_gio = date('Y-m-d H:i:s');

$loai_loc = isset($_GET['range']) ? $_GET['range'] : '7';
$ngay_bat_dau = isset($_GET['start']) ? $_GET['start'] : '';
$ngay_ket_thuc = isset($_GET['end']) ? $_GET['end'] : '';
$kho_chon = isset($_GET['hub']) ? mysqli_real_escape_string($conn, $_GET['hub']) : '';
$trang_hien_tai = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($trang_hien_tai < 1) $trang_hien_tai = 1;

$dieu_kien = "1=1";
if ($loai_loc === 'today') {
    $dieu_kien .= " AND DATE(created_at) = CURDATE()";
} elseif ($loai_loc === '30') {
    $dieu_kien .= " AND created_at >= DATE(NOW() - INTERVAL 30 DAY)";
} elseif ($loai_loc === 'custom' && $ngay_bat_dau !== '' && $ngay_ket_thuc !== '') {
    $dieu_kien .= " AND created_at >= '$ngay_bat_dau 00:00:00' AND created_at <= '$ngay_ket_thuc 23:59:59'";
} else {
    $dieu_kien .= " AND created_at >= DATE(NOW() - INTERVAL 7 DAY)";
}

if ($kho_chon !== '') {
    $dieu_kien .= " AND source_text = '$kho_chon'";
}

$sql_doanh_thu = mysqli_query($conn, "SELECT SUM(shipping_fee) as tong FROM orders WHERE status = 'delivered' AND $dieu_kien");
$doanh_thu = $sql_doanh_thu ? (float)mysqli_fetch_assoc($sql_doanh_thu)['tong'] : 0;

$sql_cod = mysqli_query($conn, "SELECT SUM(cod_amount) as tong FROM orders WHERE status IN ('pending', 'picking', 'at_hub', 'delivering') AND $dieu_kien");
$tien_cod = $sql_cod ? (float)mysqli_fetch_assoc($sql_cod)['tong'] : 0;

$sql_xe = mysqli_query($conn, "SELECT COUNT(id) as tong_xe, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as xe_chay FROM trucks");
$kq_xe = mysqli_fetch_assoc($sql_xe);
$xe_hoat_dong = $kq_xe['xe_chay'] ?? 0;
$tong_so_xe = ($kq_xe['tong_xe'] > 0) ? $kq_xe['tong_xe'] : 1;

$sql_kho = mysqli_query($conn, "SELECT h.name, h.max_capacity, (SELECT COUNT(id) FROM orders WHERE current_hub_id = h.id AND status = 'at_hub') as current_load FROM hubs h");
$kho_te_nhat = "An toàn"; $ty_le_max = 0; $mau_kho = "#10b981";
if ($sql_kho) {
    while ($kho = mysqli_fetch_assoc($sql_kho)) {
        if ($kho['max_capacity'] > 0) {
            $ty_le = $kho['current_load'] / $kho['max_capacity'];
            if ($ty_le > $ty_le_max) {
                $ty_le_max = $ty_le;
                $kho_te_nhat = $kho['name'] . " (" . round($ty_le * 100) . "%)";
                $mau_kho = $ty_le >= 0.9 ? "#ef4444" : ($ty_le >= 0.7 ? "#f59e0b" : "#10b981");
            }
        }
    }
}

$sql_dung_han = mysqli_query($conn, "SELECT COUNT(id) as sl FROM orders WHERE status NOT IN ('cancelled', 'returned') AND (sla_deadline IS NULL OR sla_deadline >= '$bay_gio' OR (status = 'delivered' AND updated_at <= sla_deadline)) AND $dieu_kien");
$dung_han = $sql_dung_han ? (int)mysqli_fetch_assoc($sql_dung_han)['sl'] : 0;

$sql_tre_han = mysqli_query($conn, "SELECT COUNT(id) as sl FROM orders WHERE status NOT IN ('delivered', 'cancelled', 'returned') AND sla_deadline < '$bay_gio' AND $dieu_kien");
$tre_han = $sql_tre_han ? (int)mysqli_fetch_assoc($sql_tre_han)['sl'] : 0;

$sql_su_co = mysqli_query($conn, "SELECT COUNT(id) as sl FROM orders WHERE status IN ('cancelled', 'returned') AND $dieu_kien");
$su_co = $sql_su_co ? (int)mysqli_fetch_assoc($sql_su_co)['sl'] : 0;

$tong_don_sla = $dung_han + $tre_han + $su_co;
$phan_tram_sla = $tong_don_sla > 0 ? round(($dung_han / $tong_don_sla) * 100) : 100;

$so_ngay_ve = ($loai_loc === '30') ? 30 : (($loai_loc === 'today') ? 1 : 7);
$thoi_gian_ket_thuc = time();
if ($loai_loc === 'custom' && $ngay_bat_dau && $ngay_ket_thuc) {
    $so_ngay_ve = round((strtotime($ngay_ket_thuc) - strtotime($ngay_bat_dau)) / 86400) + 1;
    $thoi_gian_ket_thuc = strtotime("$ngay_ket_thuc 23:59:59");
    if($so_ngay_ve > 60) $so_ngay_ve = 60; 
}

$nhan_truc_x = []; $bd_don_hang = []; $bd_doanh_thu = [];
for ($i = $so_ngay_ve - 1; $i >= 0; $i--) {
    $ngay_tinh = date('Y-m-d', $thoi_gian_ket_thuc - ($i * 86400));
    $nhan_truc_x[$ngay_tinh] = date('d/m', strtotime($ngay_tinh));
    $bd_don_hang[$ngay_tinh] = 0; 
    $bd_doanh_thu[$ngay_tinh] = 0;
}
$sql_bieu_do = mysqli_query($conn, "SELECT DATE(created_at) as ngay, COUNT(id) as so_don, SUM(shipping_fee) as dt FROM orders WHERE $dieu_kien GROUP BY DATE(created_at)");
if ($sql_bieu_do) {
    while ($dong = mysqli_fetch_assoc($sql_bieu_do)) {
        if (isset($bd_don_hang[$dong['ngay']])) {
            $bd_don_hang[$dong['ngay']] = (int)$dong['so_don'];
            $bd_doanh_thu[$dong['ngay']] = (float)$dong['dt'];
        }
    }
}

$danh_sach_loi = [];

$sql_loi_xe = mysqli_query($conn, "SELECT t.plate_number, l.cost, l.date_logged FROM truck_operating_logs l JOIN trucks t ON l.truck_id = t.id WHERE l.cost > 1000000");
if ($sql_loi_xe) {
    while ($xe = mysqli_fetch_assoc($sql_loi_xe)) {
        $danh_sach_loi[] = [
            'muc_do' => 2,
            'html_badge' => '<span style="background:#feeceb; color:#ef4444; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold;">CRITICAL</span>',
            'html_doi_tuong' => '<b>Xe tải ' . $xe['plate_number'] . '</b><br><span style="font-size:12px; color:#ef4444">Chi phí bảo trì cao</span>',
            'html_tac_dong' => '<span style="color:#2b3674; font-weight:bold;">Thiếu hụt phương tiện</span>',
            'thoi_gian' => strtotime($xe['date_logged'])
        ];
    }
}

$sql_loi_don = mysqli_query($conn, "SELECT tracking_code, dest_text, sla_deadline FROM orders WHERE status NOT IN ('delivered', 'cancelled', 'returned') AND sla_deadline < '$bay_gio' AND $dieu_kien");
if ($sql_loi_don) {
    while ($don = mysqli_fetch_assoc($sql_loi_don)) {
        $danh_sach_loi[] = [
            'muc_do' => 1,
            'html_badge' => '<span style="background:#fffbeb; color:#f59e0b; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold;">WARNING</span>',
            'html_doi_tuong' => '<b>' . $don['tracking_code'] . '</b><br><span style="font-size:12px; color:#f59e0b">Trễ giao hàng</span>',
            'html_tac_dong' => '<span style="color:#2b3674; font-weight:bold;">Khách hàng khiếu nại</span>',
            'thoi_gian' => strtotime($don['sla_deadline'])
        ];
    }
}

usort($danh_sach_loi, function($a, $b) {
    if ($a['muc_do'] === $b['muc_do']) {
        return $a['thoi_gian'] - $b['thoi_gian']; 
    }
    return $b['muc_do'] - $a['muc_do'];
});

$tong_so_loi = count($danh_sach_loi);
$tong_so_trang = ($tong_so_loi > 0) ? ceil($tong_so_loi / 3) : 1;
if ($trang_hien_tai > $tong_so_trang) $trang_hien_tai = $tong_so_trang;
$vi_tri_cat = ($trang_hien_tai - 1) * 3;

$loi_hien_thi_tren_trang = array_slice($danh_sach_loi, $vi_tri_cat, 3);

$sql_phan_tich = mysqli_query($conn, "SELECT source_text, COUNT(id) as tong_tre FROM orders WHERE status NOT IN ('delivered', 'cancelled', 'returned') AND sla_deadline < '$bay_gio' AND $dieu_kien AND source_text IS NOT NULL AND source_text != '' GROUP BY source_text ORDER BY tong_tre DESC LIMIT 1");
$kho_loi_nhieu = ""; $so_luong_loi_kho = 0;
if ($sql_phan_tich && mysqli_num_rows($sql_phan_tich) > 0) {
    $kq_pt = mysqli_fetch_assoc($sql_phan_tich);
    $kho_loi_nhieu = $kq_pt['source_text'];
    $so_luong_loi_kho = $kq_pt['tong_tre'];
}
?>

<div class="dashboard-container">
  <div class="clearfix" style="margin-bottom: 24px">
    <div style="float: left">
      <h1 style="color: #1b2559; font-size: 28px; font-weight: 700; margin-bottom: 4px;">Dashboard Vận Hành Chiến Lược</h1>
      <p style="color: #a3aed1; font-size: 14px">Phân tích dòng tiền, hiệu suất hạ tầng và dự báo rủi ro.</p>
    </div>
    <div style="float: right; margin-top: 8px">
      <div class="time-filters" id="dash-time-filters">
        <button data-range="today" class="<?php echo $loai_loc=='today'?'active':''; ?>" onclick="applyFilter('today')">Hôm nay</button>
        <button data-range="7" class="<?php echo $loai_loc=='7'?'active':''; ?>" onclick="applyFilter('7')">7 Ngày</button>
        <button data-range="30" class="<?php echo $loai_loc=='30'?'active':''; ?>" onclick="applyFilter('30')">30 Ngày</button>
        <button data-range="custom" id="btn-toggle-custom" class="<?php echo $loai_loc=='custom'?'active':''; ?>"><i class="fa-regular fa-calendar"></i> Tùy chọn</button>
      </div>

      <div id="custom-filter-box" style="display: <?php echo $loai_loc=='custom'?'block':'none'; ?>; background: white; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 10px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); position: absolute; right: 32px; z-index: 10;">
        <input type="date" id="filter-start" value="<?php echo $ngay_bat_dau; ?>" style="padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; margin-right: 8px; color: #1b2559;" />
        <input type="date" id="filter-end" value="<?php echo $ngay_ket_thuc; ?>" style="padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; margin-right: 8px; color: #1b2559;" />
        <select id="filter-hub" style="padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px; margin-right: 8px; color: #1b2559;">
          <option value="">-- Tất cả Kho --</option>
          <option value="Kho Hải Phòng" <?php if($kho_chon=='Kho Hải Phòng') echo 'selected'; ?>>Kho Hải Phòng</option>
          <option value="Kho Hà Nội" <?php if($kho_chon=='Kho Hà Nội') echo 'selected'; ?>>Kho Hà Nội</option>
          <option value="Kho Đà Nẵng" <?php if($kho_chon=='Kho Đà Nẵng') echo 'selected'; ?>>Kho Đà Nẵng</option>
          <option value="Kho Bình Dương" <?php if($kho_chon=='Kho Bình Dương') echo 'selected'; ?>>Kho Bình Dương</option>
        </select>
        <button onclick="applyCustomFilter()" style="padding: 6px 16px; background: #4318ff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Lọc</button>
      </div>
    </div>
  </div>

  <div class="row clearfix">
    <div class="col-25"><div class="dash-card">
        <i class="fa-solid fa-sack-dollar stat-icon bg-purple-light"></i>
        <div class="stat-label">Doanh thu cước</div>
        <div class="stat-value"><?php echo number_format($doanh_thu, 0, ',', '.'); ?> ₫</div>
    </div></div>
    <div class="col-25"><div class="dash-card">
        <i class="fa-solid fa-hand-holding-dollar stat-icon bg-green-light"></i>
        <div class="stat-label">COD chờ đối soát</div>
        <div class="stat-value"><?php echo number_format($tien_cod, 0, ',', '.'); ?> ₫</div>
    </div></div>
    <div class="col-25"><div class="dash-card">
        <i class="fa-solid fa-truck-fast stat-icon" style="background: #e0f2fe; color: #0284c7"></i>
        <div class="stat-label">Hiệu suất đội xe</div>
        <div class="stat-value"><?php echo "$xe_hoat_dong / $tong_so_xe Xe (".round(($xe_hoat_dong/$tong_so_xe)*100)."%)"; ?></div>
    </div></div>
    <div class="col-25"><div class="dash-card">
        <i class="fa-solid fa-warehouse stat-icon bg-red-light"></i>
        <div class="stat-label">Điểm nóng Kho bãi</div>
        <div class="stat-value" style="font-size: 18px; color: <?php echo $mau_kho; ?>"><?php echo $kho_te_nhat; ?></div>
    </div></div>
  </div>

  <div class="row clearfix">
    <div class="col-66"><div class="dash-card" style="height: 380px">
        <div class="chart-header"><div class="chart-title">Tương quan Số đơn & Doanh thu</div></div>
        <div style="height: 280px"><canvas id="lineChart"></canvas></div>
    </div></div>
    
    <div class="col-33"><div class="dash-card" style="height: 380px">
        <div class="chart-header"><div class="chart-title">Tỷ lệ Hoàn thành SLA</div></div>
        <div class="donut-wrapper">
          <canvas id="donutChart"></canvas>
          <div class="donut-text"><?php echo $phan_tram_sla; ?>%</div>
        </div>
        <div class="chart-legend">
            <div class="legend-item"><span class="legend-dot" style="background-color: #10b981"></span>Đúng hạn (<?php echo $dung_han; ?>)</div>
            <div class="legend-item"><span class="legend-dot" style="background-color: #ef4444"></span>Trễ hạn (<?php echo $tre_han; ?>)</div>
            <div class="legend-item"><span class="legend-dot" style="background-color: #f59e0b"></span>Sự cố (<?php echo $su_co; ?>)</div>
        </div>
    </div></div>
  </div>

  <div class="row clearfix">
    <div class="col-66"><div class="dash-card" style="min-height: 320px">
        <div class="chart-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
          <div class="chart-title">Cảnh Báo Vận Hành (Đa Tầng)</div>
          <div style="display: flex; align-items: center; gap: 8px">
            <button onclick="changePage(<?php echo $trang_hien_tai-1; ?>)" style="padding: 4px 12px; border: 1px solid #e2e8f0; background: white; color: #2b3674; cursor: pointer; border-radius: 4px; font-weight: bold;" <?php if($trang_hien_tai<=1) echo 'disabled'; ?>>&laquo; Trước</button>
            <span style="margin: 0 16px; font-size: 13px; color: #8f9bba; font-weight: bold;">Trang <?php echo $trang_hien_tai; ?> / <?php echo $tong_so_trang; ?></span>
            <button onclick="changePage(<?php echo $trang_hien_tai+1; ?>)" style="padding: 4px 12px; border: 1px solid #e2e8f0; background: white; color: #2b3674; cursor: pointer; border-radius: 4px; font-weight: bold;" <?php if($trang_hien_tai>=$tong_so_trang) echo 'disabled'; ?>>Sau &raquo;</button>
          </div>
        </div>
        <table class="alert-table">
          <thead><tr><th width="25%">PHÂN LOẠI</th><th width="40%">ĐỐI TƯỢNG / SỰ CỐ</th><th width="35%">TÁC ĐỘNG</th></tr></thead>
          <tbody>
            <?php 
            if (count($loi_hien_thi_tren_trang) > 0) {
                foreach ($loi_hien_thi_tren_trang as $loi) {
            ?>
            <tr>
              <td style="padding: 16px 0; border-bottom: 1px solid #e2e8f0;"><?php echo $loi['html_badge']; ?></td>
              <td style="border-bottom: 1px solid #e2e8f0;"><?php echo $loi['html_doi_tuong']; ?></td>
              <td style="border-bottom: 1px solid #e2e8f0;"><?php echo $loi['html_tac_dong']; ?></td>
            </tr>
            <?php 
                } 
            } else { 
                echo "<tr><td colspan='3' style='text-align:center; padding: 40px; color:#10b981; font-weight:bold;'>Hệ thống đang hoạt động trơn tru.</td></tr>"; 
            } 
            ?>
          </tbody>
        </table>
    </div></div>

    <div class="col-33"><div class="dash-card" style="min-height: 320px; background: #f4f7fe; border: 1px dashed #4318ff;">
        <div class="chart-header"><div class="chart-title" style="color: #4318ff"><i class="fa-solid fa-lightbulb"></i> Khai Phá Quy Luật</div></div>
        <div id="ai-insights-container">
            <?php if($so_luong_loi_kho > 0) { ?>
            <div style="background: white; padding: 12px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0;">
                <div style="font-weight:bold; color:#4318ff; margin-bottom:5px;">🔍 Mẫu (Pattern) Trễ Hạn</div>
                <p style="font-size:12px; color:#2b3674; margin-bottom:8px;">Có <b><?php echo $so_luong_loi_kho; ?> đơn hàng</b> xuất phát từ <b><?php echo $kho_loi_nhieu; ?></b> vi phạm SLA.</p>
                <div style="background:#f4f7fe; padding:6px; border-radius:4px; font-size:11px; color:#4318ff;">🚀 Kiểm tra quy trình tại <?php echo $kho_loi_nhieu; ?>.</div>
            </div>
            <?php } else { ?>
            <div style="background: white; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                <span style="color:#10b981; font-weight:bold;">Chưa phát hiện quy luật lỗi.</span>
            </div>
            <?php } ?>
        </div>
    </div></div>
  </div>
</div>

<script>
    document.getElementById("btn-toggle-custom").onclick = function() {
        var box = document.getElementById("custom-filter-box");
        box.style.display = box.style.display === "none" ? "block" : "none";
    };

    function applyFilter(range) {
        let params = "?range=" + range;
        fetch("../../../dashboard/Dashboard.php" + params).then(r => r.text()).then(html => {
            var v = document.getElementById("main-view");
            v.innerHTML = html;
            v.querySelectorAll("script").forEach(s => eval(s.innerText));
        });
    }

    function applyCustomFilter() {
        let s = document.getElementById("filter-start").value;
        let e = document.getElementById("filter-end").value;
        let h = document.getElementById("filter-hub").value;
        let params = "?range=custom&start=" + s + "&end=" + e + "&hub=" + h;
        fetch("../../../dashboard/Dashboard.php" + params).then(r => r.text()).then(html => {
            var v = document.getElementById("main-view");
            v.innerHTML = html;
            v.querySelectorAll("script").forEach(s => eval(s.innerText));
        });
    }

    function changePage(p) {
        let curRange = "<?php echo $loai_loc; ?>";
        let params = "?range=" + curRange + "&page=" + p;
        if(curRange === 'custom') {
            params += "&start=<?php echo $ngay_bat_dau; ?>&end=<?php echo $ngay_ket_thuc; ?>&hub=<?php echo $kho_chon; ?>";
        }
        fetch("../../../dashboard/Dashboard.php" + params).then(r => r.text()).then(html => {
            var v = document.getElementById("main-view");
            v.innerHTML = html;
            v.querySelectorAll("script").forEach(s => eval(s.innerText));
        });
    }

    new Chart(document.getElementById("donutChart").getContext("2d"), {
        type: "doughnut",
        data: {
            labels: ["Đúng hạn", "Trễ hạn", "Sự cố"],
            datasets: [{
                data: [<?php echo "$dung_han, $tre_han, $su_co"; ?>],
                backgroundColor: ["#10b981", "#ef4444", "#f59e0b"],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: "75%", plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById("lineChart").getContext("2d"), {
        type: "line",
        data: {
            labels: <?php echo json_encode(array_values($nhan_truc_x)); ?>,
            datasets: [
                {
                    label: "Số đơn",
                    data: <?php echo json_encode(array_values($bd_don_hang)); ?>,
                    borderColor: "#4318ff", backgroundColor: "rgba(67, 24, 255, 0.1)",
                    borderWidth: 3, tension: 0.4, fill: true, yAxisID: "y"
                },
                {
                    label: "Doanh thu (₫)",
                    data: <?php echo json_encode(array_values($bd_doanh_thu)); ?>,
                    borderColor: "#10b981", borderDash: [5, 5],
                    borderWidth: 2, tension: 0.4, yAxisID: "y1"
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 12, maxRotation: 0, minRotation: 0 } },
                y: { type: "linear", position: "left" },
                y1: { type: "linear", position: "right", grid: { drawOnChartArea: false } }
            }
        }
    });
</script>