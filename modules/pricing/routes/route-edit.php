<div class="custom-modal" id="editRouteModal">
  <div class="modal-content">
    <div class="modal-header"><h5>Sửa Tuyến Đường</h5><button type="button" class="btn-close" onclick="closeModal('editRouteModal')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="route_id" id="edit_route_id">
        <div class="form-row"><div class="form-col"><label class="form-label">Lấy hàng</label><input type="text" class="form-control" name="source_regions" id="edit_source_regions" required></div></div>
        <div class="form-row"><div class="form-col"><label class="form-label">Giao hàng</label><input type="text" class="form-control" name="dest_regions" id="edit_dest_regions" required></div></div>
        <div class="form-row">
          <div class="form-col"><label class="form-label">KL cơ bản (kg)</label><input type="number" step="0.1" class="form-control" name="base_weight" id="edit_base_weight" required></div>
          <div class="form-col"><label class="form-label">Giá cơ bản</label><input type="number" class="form-control" name="base_price" id="edit_base_price" required></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('editRouteModal')">Hủy</button><button type="submit" name="submit_edit_route" class="btn btn-primary">Lưu</button></div>
    </form>
  </div>
</div>