<div class="custom-modal" id="addTierModal">
  <div class="modal-content">
    <div class="modal-header"><h5>Thêm Bậc Giá Mới</h5><button type="button" class="btn-close" onclick="closeModal('addTierModal')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="rate_route_id" id="route_id_input">
        <div class="form-row">
          <div class="form-col"><label class="form-label">Từ (kg)</label><input type="number" step="0.01" class="form-control" id="from_weight_input" disabled></div>
          <div class="form-col"><label class="form-label">Đến (kg) <small>(Trống = Max)</small></label><input type="number" step="0.01" class="form-control" name="to_weight"></div>
        </div>
        <div class="form-row">
          <div class="form-col"><label class="form-label">Bước nhảy (kg)</label><input type="number" step="0.01" class="form-control" name="step_weight" required></div>
          <div class="form-col"><label class="form-label">Giá/bước (VNĐ)</label><input type="number" step="0.01" class="form-control" name="step_price" required></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addTierModal')">Hủy</button><button type="submit" name="submit_add_tier" class="btn btn-primary">Lưu</button></div>
    </form>
  </div>
</div>