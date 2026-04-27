<div class="custom-modal" id="editWarehouseModal">
  <div class="modal-content">
    <div class="modal-header">
      <h5>Sửa Thông Tin Kho Bãi</h5>
      <button type="button" class="btn-close" onclick="closeModal('editWarehouseModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="index.php">
        <div class="modal-body">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Mã kho (VD: WH-HN-01)</label>
                    <input type="text" class="form-control" name="code" id="edit_code" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Tên kho (VD: Hub Hà Nội)</label>
                    <input type="text" class="form-control" name="name" id="edit_name" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Loại kho</label>
                    <select class="form-select" name="type" id="edit_type" required>
                        <option value="main_hub">Hub Chính</option>
                        <option value="station">Trạm vệ tinh</option>
                    </select>
                </div>
                <div class="form-col">
                    <label class="form-label">Khu vực</label>
                    <input type="text" class="form-control" name="region" id="edit_region" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Địa chỉ chi tiết</label>
                    <textarea class="form-control" name="address" id="edit_address" rows="2" required></textarea>
                </div>
            </div>

            <div class="form-row">
              <div class="form-col">
                  <label class="form-label">Giờ mở cửa</label>
                  <input type="text" class="form-control time-picker" name="open_time" id="edit_open_time" placeholder="HH:MM" required>
              </div>
              <div class="form-col">
                  <label class="form-label">Giờ đóng cửa</label>
                  <input type="text" class="form-control time-picker" name="close_time" id="edit_close_time" placeholder="HH:MM" required>
              </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Tải trọng</label>
                    <input type="number" class="form-control" name="max_capacity" id="edit_max_capacity" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Người quản lý</label>
                    <select class="form-select" name="manager_id" id="edit_manager_id" required>
                      <option value="">-- Chọn quản lý --</option>
                      <?php if(!empty($managers)): ?>
                          <?php foreach($managers as $manager): ?>
                              <option value="<?php echo $manager['id'];?>">
                                  <?php echo htmlspecialchars($manager['full_name']); ?>
                              </option>
                          <?php endforeach; ?>
                      <?php endif; ?>
                  </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editWarehouseModal')">Hủy</button>
          <button type="submit" name="submit_edit_warehouse" class="btn btn-primary">Lưu thay đổi</button>
        </div>
    </form>
  </div>
</div>