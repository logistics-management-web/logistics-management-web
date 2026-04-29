<?php
error_reporting(E_ERROR | E_PARSE);
require_once '../../../config/config.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$id_don = (int)($_GET['id'] ?? 0);
if ($id_don <= 0) die("<div style='padding:20px; text-align:center;'>Mã đơn hàng không hợp lệ!</div>");

$sql_don = "SELECT o.*, c.full_name, c.phone 
            FROM orders o LEFT JOIN customers c ON o.customer_id = c.id 
            WHERE o.id = $id_don LIMIT 1";
$kq_don = mysqli_query($conn, $sql_don);
$don = mysqli_fetch_assoc($kq_don);

$mau_badge = 'status-gray'; $ten_badge = $don['status'];
if ($don['status'] == 'pending') { $mau_badge = 'status-green'; $ten_badge = 'Chờ xử lý'; }
elseif (in_array($don['status'], ['picking', 'delivering'])) { $mau_badge = 'status-blue'; $ten_badge = 'Đang vận hành'; }
elseif ($don['status'] == 'at_hub') { $mau_badge = 'status-yellow'; $ten_badge = 'Tại kho'; }
elseif ($don['status'] == 'delivered') { $mau_badge = 'status-green'; $ten_badge = 'Hoàn thành'; }

$danh_sach_logs = [];
$kq_logs = mysqli_query($conn, "SELECT * FROM order_logs WHERE order_id = $id_don ORDER BY created_at DESC");
if ($kq_logs) while($r = mysqli_fetch_assoc($kq_logs)) $danh_sach_logs[] = $r;

$danh_sach_docs = [];
$kq_docs = mysqli_query($conn, "SELECT * FROM order_documents WHERE order_id = $id_don ORDER BY created_at ASC");
if ($kq_docs) while($r = mysqli_fetch_assoc($kq_docs)) $danh_sach_docs[] = $r;
?>

<div class="modal-overlay open" id="order-modal-overlay" onclick="dongModalChiTiet()"></div>

<div class="modal-box open" id="order-modal-box">
  <div class="modal-header clearfix">
    <div class="modal-header-left">
      <div class="modal-title" id="detail-tracking"><?= $don['tracking_code'] ?></div>
      <div id="detail-status-badge">
        <span class="status-badge <?= $mau_badge ?>">• <?= $ten_badge ?></span>
      </div>
      <div class="modal-meta">
        SLA Hạn giao:
        <?php
        if(!empty($don['sla_deadline']) && $don['sla_deadline'] != '0000-00-00 00:00:00') {
            echo '<span class="text-orange text-bold"><i class="fa-regular fa-clock"></i> '.date('H:i:s d/m', strtotime($don['sla_deadline'])).'</span>';
        } else {
            echo '<span class="text-gray">Không có</span>';
        }
        ?>
      </div>
    </div>
    <div class="modal-header-right">
      <i class="fa-solid fa-xmark modal-close-btn" id="close-modal-btn" onclick="dongModalChiTiet()"></i>
    </div>
  </div>

  <ul class="modal-tabs">
    <li class="active" onclick="chuyenTabChiTiet(this, 'tab-tong-quan')">Tổng quan</li>
    <li onclick="chuyenTabChiTiet(this, 'tab-timeline')">Timeline</li>
    <li onclick="chuyenTabChiTiet(this, 'tab-chung-tu')">Chứng từ (<?= count($danh_sach_docs) ?>)</li>
    <li onclick="chuyenTabChiTiet(this, 'tab-logs')">Logs</li>
  </ul>

  <div id="tab-tong-quan" class="modal-body tab-content active" style="display: block;">
    <div class="modal-card clearfix">
      <div class="driver-icon-box"><i class="fa-solid fa-truck-fast"></i></div>
      <div class="driver-info">
        <p class="text-bold">Thông tin Vận chuyển</p>
        <p class="text-gray" style="font-size: 13px; margin-top: 4px">
          Tuyến đường: <?= $don['source_text'] ?> <i class="fa-solid fa-arrow-right" style="font-size: 10px; margin: 0 5px;"></i> <?= $don['dest_text'] ?>
        </p>
      </div>
    </div>

    <div class="modal-card clearfix">
      <div class="modal-card-title">
        <i class="fa-solid fa-location-dot"></i> Thông tin Giao / Nhận
      </div>

      <div style="float: left; width: 50%; padding-right: 20px">
        <p class="text-gray" style="font-size: 12px; margin-bottom: 4px">NGƯỜI GỬI</p>
        <p class="text-bold">Hệ thống MD Logistic</p>
        <p class="text-blue" style="font-size: 13px; margin-top: 4px">Tổng đài CSKH</p>
        <p class="text-gray" style="font-size: 13px; margin-top: 4px"><?= $don['source_text'] ?></p>
      </div>

      <div style="float: left; width: 50%; padding-left: 20px; border-left: 1px solid #e2e8f0;">
        <p class="text-gray" style="font-size: 12px; margin-bottom: 4px">NGƯỜI NHẬN</p>
        <p class="text-bold" id="detail-receiver-name"><?= $don['full_name'] ?: "Khách vãng lai" ?></p>
        <p class="text-blue" style="font-size: 13px; margin-top: 4px" id="detail-receiver-phone"><?= $don['phone'] ?: "Chưa có số" ?></p>
        <p class="text-gray" style="font-size: 13px; margin-top: 4px" id="detail-receiver-address"><?= $don['dest_text'] ?></p>
      </div>
    </div>

    <div class="modal-card">
      <div class="modal-card-title"><i class="fa-solid fa-box-open"></i> Hàng hóa & Phí</div>
      <div class="info-row clearfix">
        <div class="info-label">Loại hàng</div>
        <div class="info-value" id="detail-goods-type"><?= $don['goods_type'] ?: "Hàng bưu phẩm" ?></div>
      </div>
      <div class="info-row clearfix">
        <div class="info-label">Trọng lượng/KT</div>
        <div class="info-value" id="detail-goods-weight"><?= $don['weight'] ?> kg</div>
      </div>
      <div class="info-row clearfix">
        <div class="info-label">Tiền thu hộ (COD)</div>
        <div class="info-value text-red" style="font-size: 15px;"><?= number_format($don['cod_amount'], 0, ',', '.') ?> VNĐ</div>
      </div>
    </div>
  </div>

  <div id="tab-timeline" class="modal-body tab-content" style="display: none">
    <div class="timeline-wrapper">
      <div class="timeline-line"></div>
      
      <?php if(count($danh_sach_logs) > 0) { 
          $is_first = true;
          foreach($danh_sach_logs as $log) { 
              $ts = strtotime($log['created_at']);
              $icon = 'fa-box-open';
              if($log['status'] == 'pending') $icon = 'fa-file-invoice';
              if($log['status'] == 'delivering') $icon = 'fa-truck-fast';
              if($log['status'] == 'at_hub') $icon = 'fa-warehouse';
              if($log['status'] == 'delivered') $icon = 'fa-check-circle';
      ?>
      <div class="timeline-item <?= $is_first ? 'active' : '' ?>">
        <div class="timeline-time">
          <span class="time-hour"><?= date('H:i', $ts) ?></span>
          <span class="time-date"><?= date('d/m/Y', $ts) ?></span>
        </div>
        <div class="timeline-icon"><i class="fa-solid <?= $icon ?>"></i></div>
        <div class="timeline-content">
          <div class="timeline-title"><?= $log['status'] ?></div>
          <div class="timeline-desc"><?= $log['description'] ?></div>
        </div>
      </div>
      <?php $is_first = false; } } else { echo "<div style='text-align:center; padding:20px'>Chưa có lộ trình</div>"; } ?>
    </div>
  </div>

  <div id="tab-chung-tu" class="modal-body tab-content" style="display: none;">
    <div class="doc-section-title"><i class="fa-solid fa-folder-open text-blue"></i> Danh sách chứng từ (<?= count($danh_sach_docs) ?>)</div>
    <div class="doc-row clearfix">
      <?php foreach($danh_sach_docs as $doc) {
          $icon_cls = 'icon-img'; $icon_i = 'fa-image';
          if(strpos(strtolower($doc['type']), 'invoice') !== false) { $icon_cls = 'icon-pdf'; $icon_i = 'fa-file-pdf'; }
          elseif(strpos(strtolower($doc['type']), 'signature') !== false) { $icon_cls = 'icon-sign'; $icon_i = 'fa-file-signature'; }
      ?>
      <div class="doc-col">
        <div class="doc-card">
          <div class="doc-icon <?= $icon_cls ?>"><i class="fa-solid <?= $icon_i ?>"></i></div>
          <div class="doc-title"><?= $doc['type'] ?></div>
          <div class="doc-meta"><?= date('d/m/Y - H:i', strtotime($doc['created_at'])) ?></div>
          <div class="doc-actions">
            <a href="/md2/chungtu.jpg" target="_blank" class="btn btn-outline"><i class="fa-solid fa-eye"></i> Xem</a>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>
  </div>

  <div id="tab-logs" class="modal-body tab-content" style="display: none">
    <div class="logs-section-title">
      <i class="fa-solid fa-server text-gray" style="margin-right: 8px"></i> Nhật ký hệ thống (System Logs)
    </div>
    <table class="logs-table">
      <thead>
        <tr>
          <th style="width: 20%">THỜI GIAN</th>
          <th style="width: 15%">TRẠNG THÁI</th>
          <th style="width: 65%">NỘI DUNG CHI TIẾT</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($danh_sach_logs as $log) {
            $badge = 'status-gray';
            if($log['status'] == 'pending') $badge = 'status-green';
            if(in_array($log['status'], ['picking', 'delivering'])) $badge = 'status-blue';
            if($log['status'] == 'at_hub') $badge = 'status-yellow';
        ?>
        <tr>
          <td>
            <span class="log-time"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
            <span class="log-date"><?= date('d/m/Y', strtotime($log['created_at'])) ?></span>
          </td>
          <td><span class="status-badge <?= $badge ?>"><?= $log['status'] ?></span></td>
          <td><?= $log['description'] ?></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</div>

<script>
    window.dongModalChiTiet = function() {
        var container = document.getElementById("sub-container");
        if(container) {
            container.innerHTML = "";
        }
    };

    window.chuyenTabChiTiet = function(btnClicked, tabId) {
        var ul = btnClicked.parentElement;
        var allLis = ul.querySelectorAll("li");
        allLis.forEach(function(li) { li.classList.remove("active"); });

        btnClicked.classList.add("active");
        var modalBox = document.getElementById("order-modal-box");
        var allTabs = modalBox.querySelectorAll(".tab-content");
        allTabs.forEach(function(tab) {
            tab.style.display = "none";
            tab.classList.remove("active");
        });

        var targetTab = document.getElementById(tabId);
        if(targetTab) {
            targetTab.style.display = "block";
            targetTab.classList.add("active");
        }
    };
</script>