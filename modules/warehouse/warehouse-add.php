<div class="custom-modal" id="addWarehouseModal">
  <div class="modal-content">
    <div class="modal-header">
      <h5>Thêm Kho Bãi Mới</h5>
      <button type="button" class="btn-close" onclick="closeModal('addWarehouseModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="index.php">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Mã kho (VD: WH-HN-01)</label>
                    <input type="text" class="form-control" name="code" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Tên kho (VD: Hub Hà Nội)</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Loại kho</label>
                    <select class="form-select" name="type" required>
                        <option value="main_hub">Hub Chính</option>
                        <option value="station">Trạm vệ tinh</option>
                    </select>
                </div>
                <div class="form-col">
                    <label class="form-label">Khu vực</label>
                    <input type="text" class="form-control" name="region" required placeholder="VD: Miền Bắc">
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Địa chỉ chi tiết</label>
                    <textarea class="form-control" name="address" rows="2" required></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Giờ mở cửa</label>
                    <input type="text" class="form-control time-picker" name="open_time" placeholder="HH:MM" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Giờ đóng cửa</label>
                    <input type="text" class="form-control time-picker" name="close_time" placeholder="HH:MM" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Tải trọng</label>
                    <input type="number" class="form-control" name="max_capacity" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Người quản lý</label>
                    <select class="form-select" name="manager_id" required>
                      <option value="">-- Chọn quản lý --</option>
                      <?php if(!empty($managers)): ?>
                          <?php foreach($managers as $manager): ?>
                              <option value="<?php echo $manager['id'];?>">
                                  <?php echo htmlspecialchars($manager['full_name']); ?>
                              </option>
                          <?php endforeach; ?>
                      <?php else: ?>
                          <option value="" disabled>Không có quản lý nào</option>
                      <?php endif; ?>
                  </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('addWarehouseModal')">Hủy</button>
          <button type="submit" name="submit_add_warehouse" class="btn btn-primary">Lưu kho bãi</button>
        </div>
    </form>
  </div>
</div>