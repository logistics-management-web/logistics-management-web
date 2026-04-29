<div class="custom-modal" id="deleteRouteModal">
  <div class="modal-content" style="width: 400px;">
    <div class="modal-header">
      <h5 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Xác nhận xóa tuyến đường</h5>
      <button type="button" class="btn-close" onclick="closeModal('deleteRouteModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
        <div class="modal-body">
            <input type="hidden" name="delete_route_id" id="delete_route_id_input">
            <p>Bạn có muốn xóa tuyến: <strong id="delete_route_name" style="color: #2b3674;"></strong>?</p>
            <p style="color: #ef4444; font-size: 13px; margin-top: 8px;">Cảnh báo: Toàn bộ cấu hình bậc giá thuộc tuyến này cũng sẽ bị mất vĩnh viễn!</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('deleteRouteModal')">Hủy bỏ</button>
          <button type="submit" name="submit_delete_route" class="btn btn-danger">Đồng ý xóa</button>
        </div>
    </form>
  </div>
</div>s