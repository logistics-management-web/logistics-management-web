<div class="custom-modal" id="editTierModal">
  <div class="modal-content">
    <div class="modal-header"><h5>Sửa Bậc Giá</h5><button type="button" class="btn-close" onclick="closeModal('editTierModal')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="tier_id" id="edit_tier_id">
        <div class="form-row">
          <div class="form-col"><label class="form-label">Từ (kg)</label><input type="number" step="0.01" class="form-control" id="edit_tier_from" readonly></div>
          <div class="form-col"><label class="form-label">Đến (kg)</label><input type="number" step="0.01" class="form-control" name="to_weight" id="edit_tier_to"></div>
        </div>
        <div class="form-row">
          <div class="form-col"><label class="form-label">Bước (kg)</label><input type="number" step="0.01" class="form-control" name="step_weight" id="edit_tier_step_w" required></div>
          <div class="form-col"><label class="form-label">Giá/bước</label><input type="number" step="0.01" class="form-control" name="step_price" id="edit_tier_step_p" required></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editTierModal')">Hủy</button><button type="submit" name="submit_edit_tier" class="btn btn-primary">Lưu</button></div>
    </form>
  </div>
</div>