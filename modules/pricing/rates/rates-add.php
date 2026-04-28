<div class="custom-modal" id="addRateModal">
  <div class="modal-content">
    <div class="modal-header">
      <h5>Thêm Bảng Giá Mới</h5>
      <button type="button" class="btn-close" onclick="closeModal('addRateModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="index.php">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Mã bảng giá</label>
                    <input type="text" class="form-control" name="code_rates" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Tên bảng giá</label>
                    <input type="text" class="form-control" name="name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Phiên bản</label>
                    <input type="text" class="form-control" name="version" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="status" required>
                        <option value="draft">Bản nháp</option>
                        <option value="active">Áp dụng</option>
                        <option value="archived">Lưu trữ</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Ngày hiệu lực</label>
                    <input type="text" class="form-control date-picker" name="effective_date" required>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('addRateModal')">Hủy</button>
          <button type="submit" name="submit_add_rate" class="btn btn-primary">Lưu bảng giá</button>
        </div>
    </form>
  </div>
</div>