<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once "truck.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_add_log'])) {
        if (operatingLogAdd($_POST)) { echo "<script>window.location.href='operating-index.php';</script>"; exit(); }
    }
    if (isset($_POST['submit_edit_log'])) {
        if (operatingLogEdit($_POST)) { echo "<script>window.location.href='operating-index.php';</script>"; exit(); }
    }
    if (isset($_POST['submit_delete_log'])) {
        if (operatingLogDelete($_POST)) { echo "<script>window.location.href='operating-index.php';</script>"; exit(); }
    }
}

$logs = operatingLogList();
$trucks = truckList(); 
$all_drivers = driverSelectionList(); 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Nhật ký Vận hành - LogisCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f7fe; color: #2b3674; font-size: 14px; }
        
        .clearfix::after { content: ""; display: table; clear: both; }
        .text-gray { color: #8f9bba; }
        .text-bold { font-weight: 700; }
        .text-blue { color: #4318ff; }
        .text-red { color: #ef4444; }

        /* Button style Horizon */
        .btn-primary { background-color: #4318ff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; }
        .btn-primary:hover { background-color: #3311db; }
        .btn-icon { background: none; border: none; font-weight: 600; cursor: pointer; margin-right: 12px; font-size: 13px; }
        .btn-icon:hover { text-decoration: underline; }

        /* Layout */
        .page-container { padding: 32px; max-width: 1400px; margin: 0 auto; }
        .table-wrapper { background-color: white; padding: 24px; border-radius: 20px; box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.05); }
        
        /* Table Layout */
        table { width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: -12px; }
        th { font-size: 12px; color: #a3aed1; padding: 12px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f4f7fe; }
        td { padding: 16px 12px; vertical-align: middle; background-color: #ffffff; border-top: 1px solid #f4f7fe; border-bottom: 1px solid #f4f7fe; }
        tr td:first-child { border-left: 1px solid #f4f7fe; border-radius: 15px 0 0 15px; }
        tr td:last-child { border-right: 1px solid #f4f7fe; border-radius: 0 15px 15px 0; }
        
        /* Icon cho Nhật ký */
        .log-icon { float: left; width: 40px; height: 40px; line-height: 40px; text-align: center; border-radius: 12px; 
                      background-color: #fffbeb; color: #d97706; font-weight: bold; margin-right: 12px; font-size: 18px; }
        .info-container { float: left; }

        /* Status & Badges */
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; }
        .bg-light-blue { background-color: #e0eaff; color: #4318ff; }

        /* Modal Horizon */
        .modal-content { border-radius: 20px; border: none; padding: 10px; }
        .modal-header { border-bottom: none; padding: 20px 20px 5px 20px; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e2e8f0; padding: 12px; color: #2b3674; }
        .img-receipt-preview {
            display: block;
            margin: 0 auto;
            max-width: 100%;    /* Không cho ảnh vượt quá chiều rộng Modal */
            height: auto;       /* Quan trọng: Giữ đúng tỷ lệ ảnh gốc */
            max-height: 70vh;   /* Giới hạn chiều cao tối đa bằng 70% màn hình để không bị tràn */
            object-fit: contain; /* Đảm bảo ảnh nằm gọn trong khung mà không bị méo */
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="page-container">
    <div class="clearfix mb-4" style="margin-bottom: 30px;">
        <div style="float: left;">
            <p class="text-gray" style="margin-bottom: 4px;">Theo dõi vận hành</p>
            <h1 class="text-bold" style="font-size: 32px;">Nhật ký Vận hành xe</h1>
        </div>
        <div style="float: right; margin-top: 20px;">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLogModal">
                + Thêm nhật ký mới
            </button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Thông tin xe</th>
                    <th>Loại sự kiện</th>
                    <th>Mô tả chi tiết</th>
                    <th>Chi phí (VNĐ)</th>
                    <th>Hóa đơn</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): foreach ($logs as $log): ?>
                    <tr>
                        <td class="clearfix">
                            <div class="log-icon">🚛</div>
                            <div class="info-container">
                                <div class="text-bold text-blue"><?php echo htmlspecialchars($log['plate_number']); ?></div>
                                <div class="text-gray" style="font-size: 12px;"><?php echo date("d/m/Y", strtotime($log['date_logged'])); ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge bg-light-blue">
                                <?php 
                                    $types = ['repair' => 'Sửa chữa', 'fuel_refill' => 'Đổ xăng', 'maintenance' => 'Bảo dưỡng', 'accident' => 'Sự cố'];
                                    echo $types[$log['event_type']] ?? $log['event_type'];
                                ?>
                            </span>
                        </td>
                        <td style="max-width: 250px;"><span class="text-gray"><?php echo htmlspecialchars($log['description']); ?></span></td>
                        <td><span class="text-bold text-red" style="font-size: 16px;"><?php echo number_format($log['cost']); ?></span></td>
                        <td>
                            <?php if(!empty($log['receipt_url'])): ?>
                                <button type="button" class="btn-icon text-blue" data-bs-toggle="modal" data-bs-target="#receiptModal" data-img="<?php echo htmlspecialchars($log['receipt_url']); ?>">
                                    🔍 Xem ảnh
                                </button>
                            <?php else: ?>
                                <span class="text-gray small">Trống</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon text-blue" data-bs-toggle="modal" data-bs-target="#editLogModal"
                                data-id="<?php echo $log['id']; ?>"
                                data-truck="<?php echo $log['truck_id']; ?>"
                                data-driver="<?php echo $log['driver_id']; ?>"
                                data-type="<?php echo htmlspecialchars($log['event_type']); ?>"
                                data-desc="<?php echo htmlspecialchars($log['description']); ?>"
                                data-cost="<?php echo $log['cost']; ?>"
                                data-date="<?php echo $log['date_logged']; ?>">Sửa</button>

                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Xác nhận xóa nhật ký này?');">
                                <input type="hidden" name="delete_id" value="<?php echo $log['id']; ?>">
                                <button type="submit" name="submit_delete_log" class="btn-icon" style="color: #ef4444;">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-gray">Chưa có bản ghi nhật ký nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-bold">Hóa đơn chứng từ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img src="" id="receipt_image_full" class="img-receipt-preview" alt="Hóa đơn">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addLogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST" enctype="multipart/form-data">
      <div class="modal-header"><h5 class="modal-title text-bold">Thêm Nhật Ký Mới</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Chọn xe tải</label>
                  <select class="form-select" name="truck_id" required><option value="">-- Chọn xe --</option><?php foreach($trucks as $t): ?><option value="<?php echo $t['id']; ?>"><?php echo $t['plate_number']; ?></option><?php endforeach; ?></select>
              </div>
              <div class="col-md-6"><label class="form-label text-gray">Tài xế liên quan</label>
                  <select class="form-select" name="driver_id"><option value="">-- Không xác định --</option><?php foreach($all_drivers as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo $d['full_name']; ?></option><?php endforeach; ?></select>
              </div>
          </div>
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Loại sự kiện</label><input type="text" class="form-control" name="event_type" placeholder="VD: Đổ xăng, Thay lốp..." required></div>
              <div class="col-md-6"><label class="form-label text-gray">Ngày ghi nhận</label><input type="date" class="form-control" name="date_logged" value="<?php echo date('Y-m-d'); ?>" required></div>
          </div>
          <div class="mb-3"><label class="form-label text-gray">Chi phí (VNĐ)</label><input type="number" class="form-control" name="cost" required></div>
          <div class="mb-3"><label class="form-label text-gray">Ảnh hóa đơn</label><input type="file" class="form-control" name="receipt_file" accept="image/*"></div>
          <div class="mb-3"><label class="form-label text-gray">Mô tả chi tiết</label><textarea class="form-control" name="description" rows="3"></textarea></div>
      </div>
      <div class="modal-footer" style="border-top: none;"><button type="submit" name="submit_add_log" class="btn btn-primary w-100">Lưu nhật ký vận hành</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="editLogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header"><h5 class="modal-title text-bold text-blue">Sửa Nhật Ký Vận Hành</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
          <div class="row mb-3">
              <div class="col-md-4"><label class="form-label text-gray">Xe tải</label><select class="form-select" name="truck_id" id="edit_truck_id" required><?php foreach($trucks as $t): ?><option value="<?php echo $t['id']; ?>"><?php echo $t['plate_number']; ?></option><?php endforeach; ?></select></div>
              <div class="col-md-4"><label class="form-label text-gray">Tài xế</label><select class="form-select" name="driver_id" id="edit_driver_id"><option value="">-- Trống --</option><?php foreach($all_drivers as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo $d['full_name']; ?></option><?php endforeach; ?></select></div>
              <div class="col-md-4"><label class="form-label text-gray">Sự kiện</label><input type="text" class="form-control" name="event_type" id="edit_event_type" required></div>
          </div>
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Ngày ghi nhận</label><input type="date" class="form-control" name="date_logged" id="edit_date_logged" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Chi phí (VNĐ)</label><input type="number" class="form-control" name="cost" id="edit_cost" required></div>
          </div>
          <div class="mb-3"><label class="form-label text-gray">Đổi ảnh hóa đơn</label><input type="file" class="form-control" name="receipt_file" accept="image/*"></div>
          <div class="mb-3"><label class="form-label text-gray">Mô tả chi tiết</label><textarea class="form-control" name="description" id="edit_description" rows="3"></textarea></div>
      </div>
      <div class="modal-footer" style="border-top: none;"><button type="submit" name="submit_edit_log" class="btn btn-primary w-100">Cập nhật thay đổi</button></div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var editLogModal = document.getElementById('editLogModal');
if (editLogModal) {
    editLogModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; 
        this.querySelector('#edit_id').value = button.getAttribute('data-id');
        this.querySelector('#edit_truck_id').value = button.getAttribute('data-truck');
        this.querySelector('#edit_driver_id').value = button.getAttribute('data-driver');
        this.querySelector('#edit_event_type').value = button.getAttribute('data-type');
        this.querySelector('#edit_description').value = button.getAttribute('data-desc');
        this.querySelector('#edit_cost').value = button.getAttribute('data-cost');
        this.querySelector('#edit_date_logged').value = button.getAttribute('data-date');
    });
}
var receiptModal = document.getElementById('receiptModal');
if (receiptModal) {
    receiptModal.addEventListener('show.bs.modal', function (event) {
        document.getElementById('receipt_image_full').src = event.relatedTarget.getAttribute('data-img');
    });
}
</script>
</body>
</html>