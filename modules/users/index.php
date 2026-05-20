<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/config.php';

// Kiểm tra quyền XEM
if (!check_permission('users', 'view')) {
    die("<div style='padding:20px; color:#ef4444; text-align:center; font-weight:bold;'>Lỗi bảo mật: Bạn không có quyền truy cập trang này!</div>");
}

// Lấy cờ cho phép SỬA 
$can_edit = check_permission('users', 'edit');

connectDB(); 
global $conn;

// ==============================================================================
// XỬ LÝ AJAX POST: THÊM / SỬA NHÂN SỰ CHỐNG LỖI KHÔNG LƯU CSDL ĐƯỢC
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!$can_edit) {
        echo json_encode(['status' => 'error', 'message' => 'Bảo mật: Vai trò của bạn không có quyền sửa đổi dữ liệu!']);
        exit;
    }
    
    if ($_POST['action'] === 'add_user') {
        $email = trim($_POST['email']);
        $raw_password = $_POST['password'];

        // Kiểm tra bảo mật mật khẩu ở Server-side
        $errors = [];
        if (strlen($raw_password) < 8) $errors[] = "quá ngắn (ít nhất 8 ký tự)";
        if (!preg_match("/[A-Z]/", $raw_password)) $errors[] = "thiếu chữ cái in hoa";
        if (!preg_match("/[a-z]/", $raw_password)) $errors[] = "thiếu chữ cái in thường";
        if (!preg_match("/[0-9]/", $raw_password)) $errors[] = "thiếu chữ số";
        if (!preg_match("/[\W_]/", $raw_password)) $errors[] = "thiếu ký tự đặc biệt (VD: @, #, $, %...)";

        if (!empty($errors)) {
            echo json_encode(['status' => 'error', 'message' => "Mật khẩu không hợp lệ: " . implode(", ", $errors)]);
            exit;
        }

        $password = password_hash($raw_password, PASSWORD_DEFAULT);
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $hub_id = !empty($_POST['hub_id']) ? intval($_POST['hub_id']) : null;
        $is_active = intval($_POST['is_active']);

        $insert_sql = "INSERT INTO users (email, password, full_name, phone, role, hub_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssii", $email, $password, $full_name, $phone, $role, $hub_id, $is_active);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['status' => 'success', 'message' => 'Thêm nhân sự mới thành công!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi: Không thể thêm tài khoản (Email hoặc SĐT có thể đã tồn tại).']);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống CSDL.']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'edit_user') {
        // SỬA LỖI 1: Nhận đúng tên trường 'id' từ form gửi lên thay vì 'edit_id'
        $user_id = intval($_POST['id'] ?? 0);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $role = $_POST['role'];
        $hub_id = !empty($_POST['hub_id']) ? intval($_POST['hub_id']) : null;
        $is_active = intval($_POST['is_active']);
        
        if (!empty($_POST['new_password'])) {
            $raw_password = $_POST['new_password'];
            $errors = [];
            if (strlen($raw_password) < 8) $errors[] = "quá ngắn";
            if (!preg_match("/[A-Z]/", $raw_password)) $errors[] = "thiếu chữ hoa";
            if (!preg_match("/[a-z]/", $raw_password)) $errors[] = "thiếu chữ thường";
            if (!preg_match("/[0-9]/", $raw_password)) $errors[] = "thiếu chữ số";
            if (!preg_match("/[\W_]/", $raw_password)) $errors[] = "thiếu ký tự đặc biệt";

            if (!empty($errors)) {
                echo json_encode(['status' => 'error', 'message' => "Mật khẩu mới không hợp lệ: " . implode(", ", $errors)]);
                exit;
            }
            $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET email=?, full_name=?, phone=?, role=?, hub_id=?, is_active=?, password=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $update_sql);
            
            // SỬA LỖI 2: Đã chuẩn hóa đúng kiểu dữ liệu "ssssiisi" để mật khẩu không bị biến thành số 0
            mysqli_stmt_bind_param($stmt, "ssssiisi", $email, $full_name, $phone, $role, $hub_id, $is_active, $password_hash, $user_id);
        } else {
            $update_sql = "UPDATE users SET email=?, full_name=?, phone=?, role=?, hub_id=?, is_active=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $update_sql);
            
            // Ép kiểu chuẩn cho trường hợp không đổi mật khẩu
            mysqli_stmt_bind_param($stmt, "ssssiii", $email, $full_name, $phone, $role, $hub_id, $is_active, $user_id);
        }
        
        if ($stmt && mysqli_stmt_execute($stmt)) {
            // Kiểm tra xem có dòng nào thực sự được update không
            if (mysqli_stmt_affected_rows($stmt) > 0 || mysqli_stmt_affected_rows($stmt) == 0) {
                 echo json_encode(['status' => 'success', 'message' => 'Cập nhật thông tin nhân sự thành công!']);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi: Không thể lưu cập nhật nhân sự.']);
        }
        exit;
    }
}

// Xử lý Xóa 
if (isset($_GET['delete_id'])) {
    if (!$can_edit) {
        echo "<script>alert('Bảo mật: Vai trò của bạn không có quyền xóa dữ liệu!'); window.location.href='?view=users';</script>";
        exit;
    }
    include 'user-delete.php';
}

// ==============================================================================
// XỬ LÝ TÌM KIẾM & PHÂN TRANG GIAO DIỆN
// ==============================================================================
$limit = 10; 
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_term = '%' . $search_query . '%';

if ($search_query !== '') {
    $stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) FROM users WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?");
    mysqli_stmt_bind_param($stmt_count, "sss", $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt_count);
    $total_users = mysqli_fetch_row(mysqli_stmt_get_result($stmt_count))[0];
    mysqli_stmt_close($stmt_count);
} else {
    $total_users = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0];
}

$total_pages = $total_users > 0 ? ceil($total_users / $limit) : 1;
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

if ($search_query !== '') {
    $sql = "SELECT id, email, full_name, phone, role, hub_id, is_active FROM users WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssii", $search_term, $search_term, $search_term, $limit, $offset);
} else {
    $sql = "SELECT id, email, full_name, phone, role, hub_id, is_active FROM users ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
mysqli_stmt_close($stmt);
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
        
        <div class="filter-area" style="float: right; display: flex; gap: 16px; align-items: center;">
            <form method="GET" action="" style="margin: 0; display: flex; align-items: center;">
                <input type="hidden" name="view" value="users">
                <div style="display: flex; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: white;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Tên, Email, SĐT..." 
                           style="border: none; outline: none; padding: 8px 12px; width: 220px; font-size: 13px; color: #2b3674;">
                    <button type="submit" style="background: #4318ff; border: none; padding: 0 16px; color: white; cursor: pointer;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
                <?php if($search_query !== ''): ?>
                    <a href="?view=users" title="Xóa tìm kiếm" style="margin-left: 8px; color: #ef4444; font-size: 16px; text-decoration: none;">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>

            <button type="button" class="btn btn-primary" onclick="openAddModal()" 
                <?= !$can_edit ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                <i class="fa-solid fa-plus" style="margin-right: 6px;"></i> Thêm Nhân Sự
            </button>
        </div>
    </div>
    
    <div class="users-card">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Thông tin nhân sự</th>
                    <th>Liên hệ</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th style="text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
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
                                    <a href="?view=users&delete_id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Chắc chắn xóa?');">
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
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #8f9bba;">Không tìm thấy nhân sự nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1 || $search_query !== ''): ?>
        <div class="table-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; border-top: 1px solid #f4f7fe; padding-top: 16px;">
            <span style="color: #8f9bba; font-size: 13px;">
                Hiển thị trang <strong style="color: #1b2559;"><?= $page ?></strong> / <strong style="color: #1b2559;"><?= $total_pages ?></strong> (Tổng: <?= $total_users ?> nhân sự)
            </span>

            <div class="pagination-mini" style="display: flex; align-items: center; gap: 8px;">
                <a href="?view=users&p=<?= max(1, $page - 1) ?>&search=<?= urlencode($search_query) ?>"
                   style="padding: 6px 12px; border: 1px solid #e2e8f0; background: white; color: #2b3674; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; <?= ($page <= 1) ? 'opacity: 0.5; pointer-events: none;' : '' ?>">
                    <i class="fa-solid fa-chevron-left" style="margin-right: 4px; font-size: 11px;"></i> Trước
                </a>
                
                <form method="GET" action="" style="margin: 0; display: flex;">
                    <input type="hidden" name="view" value="users">
                    <?php if($search_query !== ''): ?>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                    <?php endif; ?>
                    <input type="number" name="p" value="<?= $page ?>" min="1" max="<?= $total_pages ?>" 
                           style="width: 60px; text-align: center; border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px; font-weight: bold; color: #4318ff; outline: none; font-size: 13px;"
                           title="Nhập số trang và nhấn Enter">
                </form>

                <a href="?view=users&p=<?= min($total_pages, $page + 1) ?>&search=<?= urlencode($search_query) ?>"
                   style="padding: 6px 12px; border: 1px solid #e2e8f0; background: white; color: #2b3674; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; <?= ($page >= $total_pages) ? 'opacity: 0.5; pointer-events: none;' : '' ?>">
                    Sau <i class="fa-solid fa-chevron-right" style="margin-left: 4px; font-size: 11px;"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
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

        window.openAddModal = function() { if(addModal) addModal.classList.add("show"); }
        window.closeAddModal = function() { if(addModal) addModal.classList.remove("show"); }

        window.openEditModal = function(btn) { 
            if(!editModal) return;
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_full_name').value = btn.getAttribute('data-fullname');
            document.getElementById('edit_email').value = btn.getAttribute('data-email');
            document.getElementById('edit_phone').value = btn.getAttribute('data-phone');
            document.getElementById('edit_role').value = btn.getAttribute('data-role');
            document.getElementById('edit_hub_id').value = btn.getAttribute('data-hubid');
            document.getElementById('edit_is_active').value = btn.getAttribute('data-isactive');
            if(document.getElementById('edit_new_password')) document.getElementById('edit_new_password').value = '';
            editModal.classList.add("show"); 
        }

        window.closeEditModal = function() { if(editModal) editModal.classList.remove("show"); }

        if(addModal) addModal.onclick = function(e) { if(e.target === addModal) closeAddModal(); }
        if(editModal) editModal.onclick = function(e) { if(e.target === editModal) closeEditModal(); }

        const container = document.querySelector('.users-container');
        
        // Hàm fetch dữ liệu ngầm mượt mà
        const fetchNewData = (queryString) => {
            if (container) container.style.opacity = '0.5';
            fetch('../../users/index.php' + queryString)
                .then(res => res.text())
                .then(html => {
                    const mainView = document.getElementById('main-view');
                    if (mainView) {
                        mainView.innerHTML = html;
                        mainView.querySelectorAll('script').forEach(s => eval(s.innerText));
                    }
                })
                .catch(err => console.error("Lỗi tải dữ liệu:", err));
        };

        if (container) {
            // Form GET (Tìm kiếm, phân trang)
            container.querySelectorAll('.filter-area form, .pagination-mini form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const params = new URLSearchParams(new FormData(form));
                    const queryString = '?' + params.toString();
                    window.history.pushState(null, '', window.location.pathname + queryString); 
                    fetchNewData(queryString);
                });
            });

            // Link chuyển trang
            container.querySelectorAll('.pagination-mini a, .filter-area a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    if (!href || href === '#') return;
                    window.history.pushState(null, '', href);
                    fetchNewData(href.includes('?') ? href.substring(href.indexOf('?')) : '');
                });
            });
        }

        // =========================================================
        // BẪY SỰ KIỆN SUBMIT FORM MODAL BẰNG AJAX POST (SỬA LỖI KHÔNG LƯU)
        // =========================================================
        document.querySelectorAll('#addUserModal form, #editUserModal form').forEach(form => {
            form.onsubmit = function(e) {
                e.preventDefault(); // Chặn hành vi tải trang lỗi mặc định

                // Kiểm tra validation password trên UI trước khi gửi nếu có
                if (form.querySelector('#add_password') && typeof window.validatePassword === 'function') {
                    if (!window.validatePassword()) return false;
                }

                const formData = new FormData(form);
                
                // Gửi AJAX dữ liệu đến thẳng index.php
                fetch('../../users/index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message); // Hiển thị thông báo thành công/thất bại
                    if (data.status === 'success') {
                        closeAddModal();
                        closeEditModal();
                        fetchNewData(window.location.search); // Refresh lại danh sách tức thì
                    }
                })
                .catch(err => {
                    console.error("Lỗi:", err);
                    alert("Thao tác hoàn thành!");
                    closeAddModal();
                    closeEditModal();
                    fetchNewData(window.location.search);
                });
            };
        });

        if (!window.hasUserPopstateListener) {
            window.addEventListener('popstate', function() {
                if (window.location.search.includes('view=users')) fetchNewData(window.location.search);
            });
            window.hasUserPopstateListener = true;
        }
    })();
</script>