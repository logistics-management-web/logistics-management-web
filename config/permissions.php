<?php
// File: config/permissions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Hàm kiểm tra quyền hạn tập trung cho toàn bộ hệ thống LogisCore
 * @param string $module Tên module cần kiểm tra ('dashboard', 'orders', 'trucks', etc.)
 * @param string $action Hành động thực hiện ('view' = Xem, 'edit' = Thêm/Sửa/Xóa, 'update_status' = Đổi trạng thái)
 * @return bool True nếu được phép, False nếu bị cấm
 */
function check_permission($module, $action = 'view') {
    if (!isset($_SESSION['role'])) return false;
    $role = $_SESSION['role'];

    // Quyền tối cao: admin luôn được làm mọi thứ
    if ($role === 'admin' || $role === 'super_admin') return true;

    // MA TRẬN KIỂM SOÁT TẤT CẢ CÁC MODULE CỐT LÕI
    $permissions = [
        'dashboard' => [
            'view' => ['hub_manager', 'accountant', 'order_manager']
        ],
        'orders' => [
            'view'          => ['order_manager', 'dispatcher', 'hub_manager', 'accountant'],
            'edit'          => ['order_manager'], // Chỉ Quản lý đơn được tạo/sửa/hủy đơn
            'update_status' => ['hub_manager', 'accountant', 'dispatcher'] // Role khác chỉ được quét mã đổi trạng thái
        ],
        'dispatch' => [
            'view' => ['dispatcher', 'order_manager', 'driver_manager'],
            'edit' => ['dispatcher'] // Chỉ Điều phối viên được gom chuyến, gán tài xế
        ],
        'warehouse' => [
            'view' => ['hub_manager'],
            'edit' => ['hub_manager'] // Chỉ Trưởng bưu cục được chỉnh sửa thông số kho
        ],
        'trucks' => [
            'view' => ['driver_manager', 'dispatcher', 'accountant'],
            'edit' => ['driver_manager'] // Chỉ Quản lý đội xe được thêm/sửa/xóa Xe & Tài xế
        ],
        'pricing' => [
            'view' => ['accountant', 'order_manager'],
            'edit' => ['accountant'] // Chỉ Kế toán được cấu hình biểu phí cước
        ],
        'users' => [
            'view' => ['hub_manager', 'accountant', 'order_manager', 'driver_manager', 'dispatcher'],
            'edit' => [] // Trống vì chỉ admin mới được can thiệp vào tài khoản nhân sự
        ]
    ];

    // Đối chiếu vai trò với Ma trận quyền
    if (isset($permissions[$module][$action])) {
        return in_array($role, $permissions[$module][$action]);
    }
    return false;
}
?>