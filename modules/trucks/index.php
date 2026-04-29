<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once "driver.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_add_driver'])) {
        if (driverAdd($_POST)) { echo "<script>window.location.href='index.php';</script>"; exit(); }
    }
    if (isset($_POST['submit_edit_driver'])) {
        if (driverEdit($_POST)) { echo "<script>window.location.href='index.php';</script>"; exit(); }
    }
    if (isset($_POST['submit_toggle_status'])) {
        if (driverToggleStatus($_POST)) { echo "<script>window.location.href='index.php';</script>"; exit(); }
    }
    if (isset($_POST['submit_delete_driver'])) {
        if (driverDelete($_POST['delete_id'])) { 
            echo "<script>alert('Xóa thành công!'); window.location.href='index.php';</script>"; 
            exit(); 
        }
    }
}
$drivers = driverList();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Danh sách Tài xế</title>
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

        /* Container & Table Wrapper */
        .page-container { padding: 32px; max-width: 1400px; margin: 0 auto; }
        .table-wrapper { background-color: white; padding: 24px; border-radius: 20px; box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.05); }
        
        /* Table Layout */
        table { width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: -12px; }
        th { font-size: 12px; color: #a3aed1; padding: 12px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f4f7fe; }
        td { padding: 16px 12px; vertical-align: middle; background-color: #ffffff; border-top: 1px solid #f4f7fe; border-bottom: 1px solid #f4f7fe; }
        tr td:first-child { border-left: 1px solid #f4f7fe; border-radius: 15px 0 0 15px; }
        tr td:last-child { border-right: 1px solid #f4f7fe; border-radius: 0 15px 15px 0; }
        
        /* Avatar & Text */
        .avatar-text { float: left; width: 40px; height: 40px; line-height: 40px; text-align: center; border-radius: 12px; 
                       background-color: #e0eaff; color: #4318ff; font-weight: bold; margin-right: 12px; font-size: 16px; }
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
        .form-control:focus { box-shadow: 0 0 0 3px rgba(67, 24, 255, 0.1); border-color: #4318ff; }
    </style>
</head>
<body>

<div class="page-container">
    <div class="clearfix mb-4" style="margin-bottom: 30px;">
        <div style="float: left;">
            <p class="text-gray" style="margin-bottom: 4px;">Hệ thống quản lý vận tải</p>
            <h1 class="text-bold" style="font-size: 32px;">Danh mục Tài xế</h1>
        </div>
        <div style="float: right; margin-top: 20px;">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                + Thêm tài xế mới
            </button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Mã Tài Xế</th> 
                    <th>Thông tin tài xế</th>
                    <th>Số điện thoại</th>
                    <th>Hạng bằng</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($drivers)): foreach ($drivers as $dr): ?>
                    <tr>
                        <td class="text-bold text-blue"><?php echo htmlspecialchars($dr['driver_code']); ?></td>
                        <td class="clearfix">
                            <div class="avatar-text">
                                <?php echo strtoupper(substr($dr['full_name'], 0, 1)); ?>
                            </div>
                            <div class="info-container">
                                <div class="text-bold"><?php echo htmlspecialchars($dr['full_name']); ?></div>
                                <div class="text-gray" style="font-size: 12px;">Ngày tạo: <?php echo date("d/m/Y", strtotime($dr['created_at'])); ?></div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($dr['phone']); ?></td>
                        <td>
                            <span class="status-badge status-blue">
                                <?php echo htmlspecialchars($dr['license_class']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo ($dr['work_status'] == 'active') ? 'status-green' : 'status-red'; ?>">
                                <?php echo ($dr['work_status'] == 'active') ? '● Đang hoạt động' : '● Ngoại tuyến'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-icon text-blue" 
                                data-bs-toggle="modal" data-bs-target="#editDriverModal"
                                data-id="<?php echo $dr['id']; ?>"
                                data-code="<?php echo htmlspecialchars($dr['driver_code']); ?>"
                                data-name="<?php echo htmlspecialchars($dr['full_name']); ?>"
                                data-phone="<?php echo htmlspecialchars($dr['phone']); ?>"
                                data-license="<?php echo htmlspecialchars($dr['license_class']); ?>">
                                Sửa
                            </button>

                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="toggle_id" value="<?php echo $dr['id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $dr['work_status']; ?>">
                                <button type="submit" name="submit_toggle_status" class="btn-icon <?php echo ($dr['work_status']=='active') ? 'text-gray' : 'text-bold text-blue'; ?>" onclick="return confirm('Xác nhận thay đổi trạng thái?');">
                                    <?php echo ($dr['work_status']=='active') ? 'Tắt' : 'Bật'; ?>
                                </button>
                            </form>

                            <form method="POST" style="display:inline-block;">
                                 <input type="hidden" name="delete_id" value="<?php echo $dr['id']; ?>">
                                 <button type="submit" name="submit_delete_driver" class="btn-icon" style="color: #ef4444;"
                                        onclick="return confirm('Bạn có chắc muốn xóa tài xế này?');">
                                      Xóa
                                 </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-gray">Chưa có dữ liệu tài xế.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addDriverModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-bold">Thêm Tài Xế Mới</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
              <div class="mb-3">
                  <label class="form-label text-gray">Mã tài xế (VD: TX-01)</label>
                  <input type="text" class="form-control" name="driver_code" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-gray">Họ và Tên</label>
                  <input type="text" class="form-control" name="full_name" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-gray">Số điện thoại</label>
                  <input type="text" class="form-control" name="phone" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-gray">Hạng bằng</label>
                  <select class="form-select" name="license_class" required>
                      <option value="A1">A1</option>
                      <option value="B2">B2</option>
                      <option value="C">C</option>
                  </select>
              </div>
          </div>
          <div class="modal-footer" style="border-top: none;">
            <button type="submit" name="submit_add_driver" class="btn btn-primary w-100">Lưu tài xế</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editDriverModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-bold">Sửa Thông Tin Tài Xế</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body">
              <input type="hidden" name="id" id="edit_id">
              <div class="mb-3">
                  <label class="form-label text-gray">Mã tài xế</label>
                  <input type="text" class="form-control" name="driver_code" id="edit_driver_code" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-gray">Họ và Tên</label>
                  <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-gray">Số điện thoại</label>
                  <input type="text" class="form-control" name="phone" id="edit_phone" required>
              </div>
              <div class="mb-3">
                  <label class="form-label text-gray">Hạng bằng</label>
                  <select class="form-select" name="license_class" id="edit_license_class" required>
                      <option value="A1">A1</option>
                      <option value="B2">B2</option>
                      <option value="C">C</option>
                  </select>
              </div>
          </div>
          <div class="modal-footer" style="border-top: none;">
            <button type="submit" name="submit_edit_driver" class="btn btn-primary w-100">Cập nhật thay đổi</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var editModal = document.getElementById('editDriverModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            editModal.querySelector('#edit_id').value = button.getAttribute('data-id');
            editModal.querySelector('#edit_driver_code').value = button.getAttribute('data-code');
            editModal.querySelector('#edit_full_name').value = button.getAttribute('data-name');
            editModal.querySelector('#edit_phone').value = button.getAttribute('data-phone');
            editModal.querySelector('#edit_license_class').value = button.getAttribute('data-license');
        });
    }
</script>
</body>
</html>