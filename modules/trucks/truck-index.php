<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once "truck.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_add_truck'])) {
        if (truckAdd($_POST)) { echo "<script>window.location.href='truck-index.php';</script>"; exit(); }
    }
    if (isset($_POST['submit_edit_truck'])) {
        if (truckEdit($_POST)) { echo "<script>window.location.href='truck-index.php';</script>"; exit(); }
    }
    if (isset($_POST['submit_toggle_status'])) {
        if (truckToggleStatus($_POST)) { echo "<script>window.location.href='truck-index.php';</script>"; exit(); }
    }
    if (isset($_POST['submit_delete_truck'])) {
        $truck_id = (int)$_POST['delete_id'];
        if (truckDeleteFull($truck_id)) {
            echo "<script>alert('Đã xóa xe và các dữ liệu liên quan!'); window.location.href='truck-index.php';</script>";
            exit();
        }
    }
}

$trucks = truckList();
$drivers = driverSelectionList();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Xe tải - LogisCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f7fe; color: #2b3674; font-size: 14px; }
        
        .clearfix::after { content: ""; display: table; clear: both; }
        .text-gray { color: #8f9bba; }
        .text-bold { font-weight: 700; }
        .text-blue { color: #4318ff; }

        /* Button style Horizon */
        .btn-primary { background-color: #4318ff; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 600; }
        .btn-primary:hover { background-color: #3311db; }
        .btn-icon { background: none; border: none; font-weight: 600; cursor: pointer; margin-right: 8px; font-size: 13px; }
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
        
        /* Avatar & Text cho Xe */
        .truck-icon { float: left; width: 40px; height: 40px; line-height: 40px; text-align: center; border-radius: 12px; 
                      background-color: #f4f7fe; color: #4318ff; font-weight: bold; margin-right: 12px; font-size: 14px; }
        .info-container { float: left; }

        /* Status Badge */
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; }
        .status-green { background-color: #e6faf5; color: #05cd99; }
        .status-red { background-color: #feeceb; color: #ef4444; }
        .status-blue { background-color: #e0eaff; color: #4318ff; }

        /* Modal Horizon */
        .modal-content { border-radius: 20px; border: none; padding: 10px; }
        .modal-header { border-bottom: none; padding: 20px 20px 5px 20px; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e2e8f0; padding: 12px; color: #2b3674; }
    </style>
</head>
<body>

<div class="page-container">
    <div class="clearfix mb-4" style="margin-bottom: 30px;">
        <div style="float: left;">
            <p class="text-gray" style="margin-bottom: 4px;">Quản lý đội xe</p>
            <h1 class="text-bold" style="font-size: 32px;">Danh mục Xe tải</h1>
        </div>
        <div style="float: right; margin-top: 20px;">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTruckModal">
                + Thêm xe mới
            </button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Thông tin xe</th>
                    <th>Loại xe</th>
                    <th>Tải trọng</th>
                    <th>Tài xế phụ trách</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($trucks)): foreach ($trucks as $tr): ?>
                    <tr>
                        <td class="clearfix">
                            <div class="truck-icon">
                                <?php echo substr($tr['plate_number'], 0, 3); ?>
                            </div>
                            <div class="info-container">
                                <div class="text-bold text-blue"><?php echo htmlspecialchars($tr['plate_number']); ?></div>
                                <div class="text-gray" style="font-size: 12px;"><?php echo htmlspecialchars($tr['brand_model']); ?></div>
                            </div>
                        </td>
                        <td><span class="text-bold"><?php echo htmlspecialchars($tr['truck_type']); ?></span></td>
                        <td><?php echo number_format($tr['capacity_kg']); ?> kg</td>
                        <td><?php echo $tr['driver_name'] ? htmlspecialchars($tr['driver_name']) : '<span class="text-gray">Chưa phân công</span>'; ?></td>
                        <td>
                            <span class="status-badge <?php echo ($tr['status']=='active') ? 'status-green' : 'status-red'; ?>">
                                <?php echo ($tr['status']=='active') ? '● Hoạt động' : '● Ngưng'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-icon text-blue" data-bs-toggle="modal" data-bs-target="#editTruckModal"
                                data-id="<?php echo $tr['id']; ?>"
                                data-plate="<?php echo htmlspecialchars($tr['plate_number']); ?>"
                                data-type="<?php echo htmlspecialchars($tr['truck_type']); ?>"
                                data-capacity="<?php echo $tr['capacity_kg']; ?>"
                                data-brand="<?php echo htmlspecialchars($tr['brand_model']); ?>"
                                data-driver="<?php echo $tr['main_driver_id']; ?>"
                                data-doc-type="<?php echo htmlspecialchars($tr['document_type']); ?>"
                                data-doc-num="<?php echo htmlspecialchars($tr['document_number']); ?>"
                                data-issue="<?php echo $tr['issue_date']; ?>"
                                data-expire="<?php echo $tr['expiry_date']; ?>">Sửa</button>

                            <button class="btn-icon text-blue" data-bs-toggle="modal" data-bs-target="#viewDocumentModal"
                                data-plate="<?php echo $tr['plate_number']; ?>"
                                data-type="<?php echo $tr['document_type']; ?>" 
                                data-num="<?php echo $tr['document_number']; ?>"
                                data-issue="<?php echo $tr['issue_date']; ?>"
                                data-expire="<?php echo $tr['expiry_date']; ?>" style="color: #06ebd8;">Giấy tờ</button>

                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="toggle_id" value="<?php echo $tr['id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $tr['status']; ?>">
                                <button type="submit" name="submit_toggle_status" class="btn-icon <?php echo ($tr['status']=='active') ? 'text-gray' : 'text-bold text-blue'; ?>">
                                    <?php echo ($tr['status']=='active') ? 'Tắt' : 'Bật'; ?>
                                </button>
                            </form>

                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Xác nhận xóa xe và toàn bộ dữ liệu liên quan?');">
                                <input type="hidden" name="delete_id" value="<?php echo $tr['id']; ?>">
                                <button type="submit" name="submit_delete_truck" class="btn-icon" style="color: #ef4444;">Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-gray">Không có dữ liệu xe tải.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addTruckModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST">
      <div class="modal-header">
        <h5 class="modal-title text-bold">Thêm Xe Tải Mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Biển số xe</label><input type="text" class="form-control" name="plate_number" placeholder="VD: 29C-123.45" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Loại xe</label>
                  <select class="form-select" name="truck_type">
                      <option value="Xe máy">Xe máy</option>
                      <option value="Tải Van">Tải Van</option>
                      <option value="Tải thùng kín">Tải thùng kín</option>
                  </select>
              </div>
          </div>
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Tải trọng (kg)</label><input type="number" class="form-control" name="capacity_kg" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Hãng & Model</label><input type="text" class="form-control" name="brand_model" placeholder="VD: Hino 500" required></div>
          </div>
          <div class="mb-3"><label class="form-label text-gray">Tài xế phụ trách</label>
              <select class="form-select" name="main_driver_id" required>
                  <option value="">--Chọn tài xế--</option>
                  <?php foreach($drivers as $d): ?><option value="<?php echo $d['id'];?>"><?php echo $d['full_name'];?></option><?php endforeach; ?>
              </select>
          </div>
          <h6 class="text-blue border-bottom pb-2 mb-3 mt-4 text-bold">Thông tin giấy tờ xe</h6>
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Loại giấy tờ</label><input type="text" class="form-control" name="document_type" placeholder="VD: Đăng kiểm" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Số giấy tờ</label><input type="text" class="form-control" name="document_number" required></div>
          </div>
          <div class="row">
              <div class="col-md-6"><label class="form-label text-gray">Ngày cấp</label><input type="date" class="form-control" name="issue_date" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Ngày hết hạn</label><input type="date" class="form-control" name="expiry_date" required></div>
          </div>
      </div>
      <div class="modal-footer" style="border-top: none;">
          <button type="submit" name="submit_add_truck" class="btn btn-primary w-100">Lưu thông tin xe tải</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="editTruckModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST">
      <input type="hidden" name="id" id="edit_truck_id">
      <div class="modal-header">
        <h5 class="modal-title text-bold text-blue">Sửa Thông Tin Xe</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Biển số</label><input type="text" class="form-control" name="plate_number" id="edit_plate_number" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Loại xe</label><select class="form-select" name="truck_type" id="edit_truck_type"><option value="Xe máy">Xe máy</option><option value="Tải Van">Tải Van</option><option value="Tải thùng kín">Tải thùng kín</option></select></div>
          </div>
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Tải trọng (kg)</label><input type="number" class="form-control" name="capacity_kg" id="edit_capacity_kg" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Tài xế</label><select class="form-select" name="main_driver_id" id="edit_main_driver_id"><?php foreach($drivers as $d): ?><option value="<?php echo $d['id'];?>"><?php echo $d['full_name'];?></option><?php endforeach; ?></select></div>
          </div>
          <div class="mb-3"><label class="form-label text-gray">Hãng & Model</label><input type="text" class="form-control" name="brand_model" id="edit_brand_model" required></div>
          <h6 class="text-blue border-bottom pb-2 mb-3 mt-4 text-bold">Giấy tờ xe</h6>
          <div class="row mb-3">
              <div class="col-md-6"><label class="form-label text-gray">Loại giấy tờ</label><input type="text" class="form-control" name="document_type" id="edit_document_type" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Số giấy tờ</label><input type="text" class="form-control" name="document_number" id="edit_document_number" required></div>
          </div>
          <div class="row">
              <div class="col-md-6"><label class="form-label text-gray">Ngày cấp</label><input type="date" class="form-control" name="issue_date" id="edit_issue_date" required></div>
              <div class="col-md-6"><label class="form-label text-gray">Ngày hết hạn</label><input type="date" class="form-control" name="expiry_date" id="edit_expiry_date" required></div>
          </div>
      </div>
      <div class="modal-footer" style="border-top: none;">
          <button type="submit" name="submit_edit_truck" class="btn btn-primary w-100">Cập nhật thay đổi</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-bold">Giấy tờ xe: <span class="text-blue" id="doc_plate_title"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="p-4 bg-light rounded-4">
              <div class="row">
                  <div class="col-md-6 mb-3 mb-md-0">
                      <p class="mb-2 text-gray">Loại giấy tờ:</p>
                      <p class="text-bold mb-4" id="view_document_type" style="font-size: 16px;"></p>
                      
                      <p class="mb-2 text-gray">Số giấy tờ:</p>
                      <p class="text-bold" id="view_document_number" style="font-size: 16px;"></p>
                  </div>

                  <div class="col-md-6 ps-md-4">
                      <p class="mb-2 text-gray">Ngày ghi nhận cấp:</p>
                      <p class="text-bold mb-4" id="view_issue_date" style="font-size: 16px;"></p>
                      
                      <p class="mb-2 text-gray">Ngày hết hạn dự kiến:</p>
                      <p class="text-bold text-red" id="view_expire_date" style="font-size: 16px;"></p>
                  </div>
              </div>
          </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JS Đổ dữ liệu Sửa
    var editModal = document.getElementById('editTruckModal');
    editModal.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget;
        this.querySelector('#edit_truck_id').value = b.getAttribute('data-id');
        this.querySelector('#edit_plate_number').value = b.getAttribute('data-plate');
        this.querySelector('#edit_truck_type').value = b.getAttribute('data-type');
        this.querySelector('#edit_capacity_kg').value = b.getAttribute('data-capacity');
        this.querySelector('#edit_brand_model').value = b.getAttribute('data-brand');
        this.querySelector('#edit_main_driver_id').value = b.getAttribute('data-driver');
        this.querySelector('#edit_document_type').value = b.getAttribute('data-doc-type');
        this.querySelector('#edit_document_number').value = b.getAttribute('data-doc-num');
        this.querySelector('#edit_issue_date').value = b.getAttribute('data-issue');
        this.querySelector('#edit_expiry_date').value = b.getAttribute('data-expire');
    });

    // JS Đổ dữ liệu Giấy tờ
    var viewDocModal = document.getElementById('viewDocumentModal');
    viewDocModal.addEventListener('show.bs.modal', function (e) {
        var b = e.relatedTarget;
        document.getElementById('doc_plate_title').innerText = b.getAttribute('data-plate');
        document.getElementById('view_document_type').innerText = b.getAttribute('data-type') || 'N/A';
        document.getElementById('view_document_number').innerText = b.getAttribute('data-num') || 'N/A';
        document.getElementById('view_issue_date').innerText = b.getAttribute('data-issue') || 'N/A';
        document.getElementById('view_expire_date').innerText = b.getAttribute('data-expire') || 'N/A';
    });
</script>
</body>
</html>