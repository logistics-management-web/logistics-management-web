<div class="custom-modal" id="deleteRateModal">
  <div class="modal-content" style="width: 400px;">
    <div class="modal-header">
      <h5 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Xác nhận xóa</h5>
      <button type="button" class="btn-close" onclick="closeModal('deleteRateModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="index.php">
        <div class="modal-body">
            <input type="hidden" name="delete_id" id="delete_rate_id_input">
            <p>Bạn có chắc chắn muốn xóa bảng giá <strong id="delete_rate_name" style="color: #2b3674;"></strong> không?</p>
            <p style="color: #ef4444; font-size: 13px; margin-top: 8px;">Hành động này không thể hoàn tác!</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeModal('deleteRateModal')">Hủy bỏ</button>
          <button type="submit" name="submit_delete_rate" class="btn btn-danger">Đồng ý xóa</button>
        </div>
    </form>
  </div>
</div>