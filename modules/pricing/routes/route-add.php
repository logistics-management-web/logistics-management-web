<div class="custom-modal" id="addRouteModal">
  <div class="modal-content">
    <div class="modal-header"><h5>Thêm Tuyến Đường Mới</h5><button type="button" class="btn-close" onclick="closeModal('addRouteModal')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="rate_id" value="<?php echo $rate_id ?? ''; ?>">
        <div class="form-row"><div class="form-col"><label class="form-label">Quận/Tỉnh lấy hàng</label><input type="text" class="form-control" name="source_regions" required></div></div>
        <div class="form-row"><div class="form-col"><label class="form-label">Quận/Tỉnh giao hàng</label><input type="text" class="form-control" name="dest_regions" required></div></div>
        <div class="form-row">
          <div class="form-col"><label class="form-label">Khối lượng cơ bản (kg)</label><input type="number" step="0.1" class="form-control" name="base_weight" required></div>
          <div class="form-col"><label class="form-label">Giá cơ bản (VNĐ)</label><input type="number" class="form-control" name="base_price" required></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('addRouteModal')">Hủy</button><button type="submit" name="submit_add_route" class="btn btn-primary">Lưu</button></div>
    </form>
  </div>
</div>