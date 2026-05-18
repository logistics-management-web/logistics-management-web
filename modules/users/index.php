<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/config.php';

// Kiểm tra quyền XEM (Nếu không có quyền xem thì văng ra luôn)
if (!check_permission('users', 'view')) {
    die("<div style='padding:20px; color:#ef4444; text-align:center; font-weight:bold;'>Lỗi bảo mật: Bạn không có quyền truy cập trang này!</div>");
}

// Lấy cờ cho phép SỬA (Thêm/Sửa/Xóa) để tắt/mở nút
$can_edit = check_permission('users', 'edit');

connectDB(); 
global $conn;

// Xử lý Xóa (Kiểm tra lại quyền ngay trên backend)
if (isset($_GET['delete_id'])) {
    if (!$can_edit) {
        echo "<script>alert('Bảo mật: Vai trò của bạn không có quyền xóa dữ liệu!'); window.location.href='../orders/management/main_orders.php?view=users';</script>";
        exit;
    }
    include 'user-delete.php';
}

$sql = "SELECT id, email, full_name, phone, role, hub_id, is_active FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>

<link rel="stylesheet" href="../../users/users.css">

<div class="page-container users-container">
    <div class="page-header clearfix">
        <div class="title-area" style="float: left;">
            <h1 class="page-main-title" style="font-weight: 700; color: #1b2559;">Nhân sự hệ thống</h1>
            <p class="page-desc" style="color: #a3aed1; margin-top: 4px;">
                <?= $can_edit ? 'Chế độ: Quản trị toàn quyền' : 'Chế độ: Chỉ xem dữ liệu (Read-only)' ?>
            </p>
        </div>
        <div class="filter-area" style="float: right;">
            <button type="button" class="btn btn-primary" onclick="openAddModal()" 
                <?= !$can_edit ? 'disabled style="opacity:0.5; cursor:not-allowed;" title="Chỉ Quản trị viên mới được thao tác"' : '' ?>>
                <i class="fa-solid fa-plus" style="margin-right: 6px;"></i> Thêm Nhân Sự
            </button>
        </div>
    </div>
    
    <div class="users-card">
        <table class="user-table">
            <tbody>
                <?php foreach ($users as $user): 
                    $avatar_letter = strtoupper(mb_substr($user['full_name'], 0, 1, "UTF-8"));
                ?>
                <tr>
                    <td>
                        <div class="user-info-cell">
                            <div class="user-avatar-circle"><?= $avatar_letter ?></div>
                            <div>
                                <strong style="color: #1b2559; font-size: 14px;"><?= htmlspecialchars($user['full_name']) ?></strong>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #2b3674; font-size: 13px;"><?= htmlspecialchars($user['phone']) ?></div>
                        <div class="user-meta-sub"><?= htmlspecialchars($user['email']) ?></div>
                    </td>
                    <td>
                        <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($user['is_active']): ?>
                            <span class="status-badge status-active"><i class="fa-solid fa-circle-check"></i> Active</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive"><i class="fa-solid fa-lock"></i> Locked</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <div class="action-buttons" style="justify-content: flex-end;">
                            <button type="button" class="btn btn-sm btn-edit" title="Chỉnh sửa"
                               onclick="openEditModal(this)"
                               <?= !$can_edit ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : '' ?>
                               data-id="<?= $user['id'] ?>"
                               data-fullname="<?= htmlspecialchars($user['full_name']) ?>"
                               data-email="<?= htmlspecialchars($user['email']) ?>"
                               data-phone="<?= htmlspecialchars($user['phone']) ?>"
                               data-role="<?= $user['role'] ?>"
                               data-hubid="<?= htmlspecialchars($user['hub_id'] ?? '') ?>"
                               data-isactive="<?= $user['is_active'] ?>">
                               <i class="fa-solid fa-pen"></i>
                            </button> 
                            
                            <?php if ($can_edit): ?>
                                <a href="index.php?delete_id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Chắc chắn xóa?');">
                                   <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-danger" disabled style="opacity:0.4; cursor:not-allowed;">
                                   <i class="fa-solid fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($can_edit): ?>
    <div id="addUserModal" class="custom-modal"><div class="modal-content"><?php include 'user-add.php'; ?></div></div>
    <div id="editUserModal" class="custom-modal"><div class="modal-content"><?php include 'user-edit.php'; ?></div></div>
<?php endif; ?>

<script>
    (function() {
        const addModal = document.getElementById("addUserModal");
        const editModal = document.getElementById("editUserModal");

        window.openAddModal = function() { addModal.classList.add("show"); }
        window.closeAddModal = function() { addModal.classList.remove("show"); }

        window.openEditModal = function(btn) { 
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_full_name').value = btn.getAttribute('data-fullname');
            document.getElementById('edit_email').value = btn.getAttribute('data-email');
            document.getElementById('edit_phone').value = btn.getAttribute('data-phone');
            document.getElementById('edit_role').value = btn.getAttribute('data-role');
            document.getElementById('edit_hub_id').value = btn.getAttribute('data-hubid');
            document.getElementById('edit_is_active').value = btn.getAttribute('data-isactive');
            
            if(document.getElementById('edit_new_password')) {
                document.getElementById('edit_new_password').value = '';
            }
            editModal.classList.add("show"); 
        }

        window.closeEditModal = function() { editModal.classList.remove("show"); }

        addModal.onclick = function(e) { if(e.target === addModal) closeAddModal(); }
        editModal.onclick = function(e) { if(e.target === editModal) closeEditModal(); }
    })();
</script>