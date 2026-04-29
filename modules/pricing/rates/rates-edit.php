<div class="custom-modal" id="editRateModal">
  <div class="modal-content">
    <div class="modal-header">
      <h5>Sửa Thông Tin Bảng Giá</h5>
      <button type="button" class="btn-close" onclick="closeModal('editRateModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="index.php">
        <div class="modal-body">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Mã bảng giá</label>
                    <input type="text" class="form-control" name="code_rates" id="edit_code_rates" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Tên bảng giá</label>
                    <input type="text" class="form-control" name="name" id="edit_name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Phiên bản</label>
                    <input type="text" class="form-control" name="version" id="edit_version" required>
                </div>
                <div class="form-col">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" name="status" id="edit_status" required>
                        <option value="draft">Bản nháp</option>
                        <option value="active">Áp dụng</option>
                        <option value="archived">Lưu trữ</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-col">
                    <label class="form-label">Ngày hiệu lực</label>
                    <input type="text" class="form-control date-picker" name="effective_date" id="edit_effective_date" required>
                </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editRateModal')">Hủy</button>
          <button type="submit" name="submit_edit_rate" class="btn btn-primary">Lưu thay đổi</button>
        </div>
    </form>
  </div>
</div>