<?php
error_reporting(E_ERROR | E_PARSE);
require_once '../../../config/config.php';
global $conn;
connectDB();
date_default_timezone_set('Asia/Ho_Chi_Minh');
$bay_gio = date('Y-m-d H:i:s');

function layConSo($conn, $sql) {
    $ket_qua = mysqli_query($conn, $sql);
    if ($ket_qua) {
        $dong = mysqli_fetch_assoc($ket_qua);
        return (int)$dong['sl'];
    }
    return 0;
}

$trang_thai = $_GET['status'] ?? 'all';
$tu_ngay = $_GET['from'] ?? '';
$den_ngay = $_GET['to'] ?? '';
$cod_min = (isset($_GET['min_cod']) && $_GET['min_cod'] !== '') ? (float)$_GET['min_cod'] : -1;
$cod_max = (isset($_GET['max_cod']) && $_GET['max_cod'] !== '') ? (float)$_GET['max_cod'] : -1;
$trang_hien_tai = (int)($_GET['page'] ?? 1);
if ($trang_hien_tai < 1) $trang_hien_tai = 1;
$limit = 7;

$tong_don_he_thong = layConSo($conn, "SELECT COUNT(id) as sl FROM orders");
$dang_giao_he_thong = layConSo($conn, "SELECT COUNT(id) as sl FROM orders WHERE status = 'delivering'");
$cho_xu_ly_he_thong = layConSo($conn, "SELECT COUNT(id) as sl FROM orders WHERE status = 'pending'");

$sql_sla = "SELECT COUNT(id) as sl FROM orders WHERE status NOT IN ('delivered', 'cancelled', 'returned') AND sla_deadline < '$bay_gio'";
$sla_canh_bao = layConSo($conn, $sql_sla);

$where = "1=1";
if ($trang_thai === 'pending') $where .= " AND o.status = 'pending'";
elseif ($trang_thai === 'processing') $where .= " AND o.status IN ('picking', 'at_hub', 'delivering')";
elseif ($trang_thai === 'delivered') $where .= " AND o.status = 'delivered'";
elseif ($trang_thai === 'issues') $where .= " AND o.status IN ('returning', 'returned', 'cancelled')";

if ($tu_ngay != '') $where .= " AND DATE(o.created_at) >= '$tu_ngay'";
if ($den_ngay != '') $where .= " AND DATE(o.created_at) <= '$den_ngay'";
if ($cod_min >= 0) $where .= " AND o.cod_amount >= $cod_min";
if ($cod_max >= 0) $where .= " AND o.cod_amount <= $cod_max";

$tong_don_sau_loc = layConSo($conn, "SELECT COUNT(o.id) as sl FROM orders o WHERE $where");
$tong_so_trang = ceil($tong_don_sau_loc / $limit);
if ($tong_so_trang < 1) $tong_so_trang = 1;
if ($trang_hien_tai > $tong_so_trang) $trang_hien_tai = $tong_so_trang;
$offset = ($trang_hien_tai - 1) * $limit;

$sql_table = "SELECT o.*, c.full_name FROM orders o 
              LEFT JOIN customers c ON o.customer_id = c.id 
              WHERE $where 
              ORDER BY o.id DESC LIMIT $limit OFFSET $offset";
$res_table = mysqli_query($conn, $sql_table);
?>

<div class="page-container">
    <div class="clearfix">
        <div class="page-title-area">
            <h1>Quản lý Đơn hàng</h1>
            <p class="text-gray">Quản lý, theo dõi và điều phối đơn hàng trên toàn hệ thống.</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline"><i class="fa-solid fa-download"></i> Xuất file</button>
            <button class="btn btn-primary" onclick="moFormTaoDon()"><i class="fa-solid fa-plus"></i> Tạo đơn mới</button>
        </div>
    </div>

    <div class="stats-row clearfix">
        <div class="col-4"><div class="stat-card">
            <span class="text-gray text-bold">Tổng đơn hàng</span><span class="badge badge-green">↗ 5.2%</span>
            <div class="stat-value" id="stat-total"><?= number_format($tong_don_he_thong, 0, ',', '.') ?></div> 
        </div></div>
        <div class="col-4"><div class="stat-card">
            <span class="text-gray text-bold">Đang giao</span><span class="badge badge-green">↗ 2.1%</span>
            <div class="stat-value text-blue" id="stat-delivering"><?= number_format($dang_giao_he_thong, 0, ',', '.') ?></div>
        </div></div>
        <div class="col-4"><div class="stat-card">
            <span class="text-gray text-bold">Chờ xử lý</span><span class="badge badge-orange">— 0.0%</span>
            <div class="stat-value text-orange" id="stat-pending"><?= number_format($cho_xu_ly_he_thong, 0, ',', '.') ?></div>
        </div></div>
        <div class="col-4"><div class="stat-card">
            <span class="text-gray text-bold">Cảnh báo SLA</span><span class="badge badge-red">↘ 1.5%</span>
            <div class="stat-value text-red" id="stat-sla" style="font-size: 28px; font-weight: 700; line-height: 1.2; margin-top: 5px;">
                <?= number_format($sla_canh_bao, 0, ',', '.') ?>
            </div>
        </div></div>
    </div>

    <div class="table-wrapper">
        <div class="clearfix">
            <ul class="tabs">
                <li class="<?= $trang_thai=='all'?'active':'' ?>" onclick="window.loadOrderPage('?status=all')">Tất cả</li>
                <li class="<?= $trang_thai=='pending'?'active':'' ?>" onclick="window.loadOrderPage('?status=pending')">Chờ xử lý</li>
                <li class="<?= $trang_thai=='processing'?'active':'' ?>" onclick="window.loadOrderPage('?status=processing')">Đang vận hành</li>
                <li class="<?= $trang_thai=='delivered'?'active':'' ?>" onclick="window.loadOrderPage('?status=delivered')">Hoàn thành</li>
                <li class="<?= $trang_thai=='issues'?'active':'' ?>" onclick="window.loadOrderPage('?status=issues')">Sự cố</li>
            </ul>
            <div class="table-actions">
                <button class="btn btn-outline" onclick="moModalLoc()"><i class="fa-solid fa-filter"></i> Lọc nâng cao</button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" /></th>
                    <th>MÃ ĐƠN</th>
                    <th>KHÁCH HÀNG</th>
                    <th>TUYẾN ĐƯỜNG</th>
                    <th>TRẠNG THÁI</th>
                    <th>SLA (HẠN GIAO)</th>
                    <th>COD (VNĐ)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                while ($don = mysqli_fetch_assoc($res_table)) { 
                    $mau = 'status-gray'; $ten = $don['status'];
                    if ($don['status'] == 'pending') { $mau = 'status-orange'; $ten = 'Chờ xử lý'; }
                    elseif (in_array($don['status'], ['picking', 'at_hub', 'delivering'])) { $mau = 'status-blue'; $ten = 'Đang vận hành'; }
                    elseif ($don['status'] == 'delivered') { $mau = 'status-green'; $ten = 'Hoàn thành'; }
                    elseif (in_array($don['status'], ['cancelled', 'returned'])) { $mau = 'status-red'; $ten = 'Sự cố'; }

                    $arr = explode(',', $don['dest_text']);
                    $dc = count($arr) >= 2 ? trim($arr[count($arr)-2]) . ", " . trim($arr[count($arr)-1]) : $don['dest_text'];

                    $sla_view = '<td style="color: #8f9bba; font-weight: normal; font-size: 14px;">Không có</td>';
                    if ($don['status'] == 'pending') {
                        $sla_view = '<td style="color: #8f9bba; font-weight: normal; font-size: 14px; font-style: italic;">Đợi lấy hàng...</td>';
                    } elseif (!empty($don['sla_deadline']) && $don['sla_deadline'] != '0000-00-00 00:00:00') {
                        $han_sla = strtotime($don['sla_deadline']);
                        $da_xong = in_array($don['status'], ['delivered', 'cancelled', 'returned']);
                        $hien_tai = time();

                        if ($da_xong) {
                            $sla_view = '<td style="color: #8f9bba; font-weight: normal; font-size: 14px;">Đã chốt hạn</td>';
                        } elseif ($han_sla < $hien_tai) {
                            $phut_tre = floor(($hien_tai - $han_sla) / 60);
                            $chuoi_tre = ($phut_tre < 60) ? "{$phut_tre} phút" : floor($phut_tre / 60) . " giờ " . ($phut_tre % 60) . "p";
                            $sla_view = '<td class="text-red" style="font-weight: 700; font-size: 14px;"><i class="fa-solid fa-triangle-exclamation"></i> Trễ ' . $chuoi_tre . '</td>';
                        } else {
                            $sla_view = '<td class="text-orange" style="font-weight: 700; font-size: 14px;"><i class="fa-regular fa-clock"></i> ' . date('H:i d/m', $han_sla) . '</td>';
                        }
                    }
                ?>
                <tr class="order-row-item" onclick="moChiTietDon(<?= $don['id'] ?>)" style="cursor: pointer;">
                    <td onclick="event.stopPropagation()"><input type="checkbox" /></td>
                    <td><b class="text-bold"><?= $don['tracking_code'] ?></b></td>
                    <td><b class="text-bold"><?= $don['full_name'] ?: "Khách vãng lai" ?></b></td>
                    <td class="text-bold"><?= $don['source_text'] ?> → <?= $dc ?></td>
                    <td><span class="status-badge <?= $mau ?>">• <?= $ten ?></span></td>
                    <?= $sla_view ?>
                    <td class="text-bold"><?= number_format($don['cod_amount'], 0, ',', '.') ?> đ</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="pagination clearfix">
            <div class="page-info">Hiển thị <b style="color:#2b3674"><?= $offset+1 ?></b> đến <b style="color:#2b3674"><?= min($offset+$limit, $tong_don_sau_loc) ?></b> trong <b style="color:#2b3674"><?= $tong_don_sau_loc ?></b> kết quả</div>
            <div class="page-controls">
                <?php for($i=1; $i<=$tong_so_trang; $i++) { ?>
                    <button class="page-btn <?= ($i==$trang_hien_tai)?'active':'' ?>" onclick="window.loadOrderPage('?status=<?= $trang_thai ?>&page=<?= $i ?>')"><?= $i ?></button>
                <?php } ?>
            </div>
        </div>
    </div> </div> 
    <div class="modal-overlay" id="create-order-overlay" onclick="dongFormTaoDon()"></div>

<div class="modal-box" id="create-order-box">
  <div class="modal-header clearfix">
    <div class="modal-title" style="float: left; font-size: 20px">
      <i class="fa-solid fa-file-circle-plus text-blue"></i> Tạo Đơn Hàng Mới
    </div>
    <div style="float: right">
      <i class="fa-solid fa-xmark modal-close-btn" id="close-create-btn" onclick="dongFormTaoDon()" style="font-size: 20px; cursor: pointer;"></i>
    </div>
  </div>

  <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
    <div class="form-section-title">1. Thông tin Giao / Nhận</div>
    <div class="form-row clearfix">
      <div class="form-col-50">
        <div class="form-group">
          <label class="form-label">Điểm lấy hàng (Kho/Hub) <span class="required">*</span></label>
          <select class="form-control" id="input-source">
            <option value="">-- Chọn kho xuất phát --</option>
            <option value="Hub Tổng Hà Nội">Hub Tổng Hà Nội</option>
            <option value="Hub Tổng TP.HCM">Hub Tổng TP.HCM</option>
            <option value="Hub Tổng Đà Nẵng">Hub Tổng Đà Nẵng</option>
            <option value="Kho Hải Phòng">Kho Hải Phòng</option>
          </select>
        </div>
      </div>
      <div class="form-col-50">
        <div class="form-group">
          <label class="form-label">Số điện thoại gửi <span class="required">*</span></label>
          <input type="text" class="form-control" value="0987654321" disabled style="background-color: #f4f7fe" />
        </div>
      </div>
    </div>

    <div class="form-row clearfix">
      <div class="form-col-50">
        <div class="form-group">
          <label class="form-label">Số điện thoại nhận <span class="required">*</span></label>
          <input type="text" id="input-phone-receive" class="form-control" placeholder="Nhập SĐT nhận để tự động tìm khách" />
        </div>
      </div>
      <div class="form-col-50">
        <div class="form-group">
          <label class="form-label">Tên người nhận <span class="required">*</span></label>
          <input type="text" id="input-customer-name" class="form-control" placeholder="Nhập tên người nhận" />
        </div>
      </div>
    </div>

    <div class="form-row clearfix">
      <div class="form-col-50">
        <div class="form-group">
          <label class="form-label">Tỉnh / Thành phố <span class="required">*</span></label>
          <select id="input-city" class="form-control">
            <option value="">-- Chọn Tỉnh/Thành --</option>
            <option value="Hà Nội">Hà Nội</option>
            <option value="TP.HCM">TP.HCM</option>
            <option value="Hải Phòng">Hải Phòng</option>
            <option value="Đà Nẵng">Đà Nẵng</option>
          </select>
        </div>
      </div>
      <div class="form-col-50">
        <div class="form-group">
          <label class="form-label">Phường/Xã <span class="required">*</span></label>
          <select id="input-ward" class="form-control">
            <option value="">-- Chọn Phường --</option>
            <option value="Thủ Đức">Thủ Đức</option>
            <option value="Cầu Giấy">Cầu Giấy</option>
            <option value="An Biên">An Biên</option>
            <option value="Hải Châu">Hải Châu</option>
          </select>
        </div>
      </div>
    </div>

    <div class="form-row clearfix">
      <div class="form-col-100">
        <div class="form-group">
          <label class="form-label">Địa chỉ chi tiết <span class="required">*</span></label>
          <input type="text" id="input-address-detail" class="form-control" placeholder="VD: Số nhà 48/261 Trần Nguyên Hãn" />
        </div>
      </div>
    </div>

    <div class="form-section-title">2. Chi tiết Hàng hóa & Cước phí</div>

    <div class="form-row clearfix">
      <div class="form-col-50">
        <div class="form-group">
          <label class="form-label">Loại hàng hóa <span class="required">*</span></label>
          <input type="text" id="input-goods-type" class="form-control" placeholder="VD: Quần áo, Điện tử..." />
        </div>
      </div>
      <div class="form-col-50">
        <div class="form-group">
          <label class="form-label">Tiền Thu hộ (COD)</label>
          <div class="input-group">
            <input type="text" id="input-cod" class="form-control" placeholder="0" />
            <span class="input-addon">VNĐ</span>
          </div>
        </div>
      </div>
    </div>

    <div class="form-row clearfix">
      <div class="form-col-25">
        <div class="form-group">
          <label class="form-label">Trọng lượng <span class="required">*</span></label>
          <div class="input-group">
            <input type="text" id="input-weight" class="form-control" placeholder="0.0" />
            <span class="input-addon">kg</span>
          </div>
        </div>
      </div>
      <div class="form-col-25"><div class="form-group"><label class="form-label">Dài</label><div class="input-group"><input type="text" class="form-control" placeholder="0" /><span class="input-addon">cm</span></div></div></div>
      <div class="form-col-25"><div class="form-group"><label class="form-label">Rộng</label><div class="input-group"><input type="text" class="form-control" placeholder="0" /><span class="input-addon">cm</span></div></div></div>
      <div class="form-col-25"><div class="form-group"><label class="form-label">Cao</label><div class="input-group"><input type="text" class="form-control" placeholder="0" /><span class="input-addon">cm</span></div></div></div>
    </div>
    
    <div class="form-row clearfix">
      <div class="form-col-100">
        <div class="form-group">
          <label class="form-label">Ghi chú vận chuyển (Note)</label>
          <textarea class="form-control" placeholder="VD: Hàng dễ vỡ xin nhẹ tay, Giao trong giờ hành chính..."></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-footer">
    <button class="btn btn-outline" id="cancel-create-btn" onclick="dongFormTaoDon()">Hủy bỏ</button>
    <button id="btn-save-order" class="btn btn-primary" onclick="luuDonMoi()">
      <i class="fa-solid fa-check"></i> Lưu & Tạo đơn
    </button>
  </div>
</div>
</div>

<script>

    function moModalLoc() {
        document.getElementById('filter-overlay').classList.add('open');
        document.getElementById('filter-box').classList.add('open');
    }
    function dongModalLoc() {
        document.getElementById('filter-overlay').classList.remove('open');
        document.getElementById('filter-box').classList.remove('open');
    }
    function apDungLoc() {
        let f = document.getElementById('filter-date-from').value;
        let t = document.getElementById('filter-date-to').value;
        let mi = document.getElementById('filter-cod-min').value;
        let ma = document.getElementById('filter-cod-max').value;
        dongModalLoc();
        window.loadOrderPage(`?status=<?= $trang_thai ?>&from=${f}&to=${t}&min_cod=${mi}&max_cod=${ma}&page=1`);
    }
</script>