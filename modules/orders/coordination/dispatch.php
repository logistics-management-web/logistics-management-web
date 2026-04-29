<?php
session_start();
require_once "../../../config/config.php";

// Khởi tạo kết nối DB
connectDB();
global $conn;

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login/login.php");
    exit;
}

$dispatcher_name = $_SESSION["username"] ?? 'Admin User';
$message = "";

// ==============================================================================
// PHẦN 1: XỬ LÝ DỮ LIỆU POST (THAO TÁC) - GIỮ NGUYÊN LOGIC CỦA BẠN
// ==============================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $order_id = $_POST['order_id'] ?? null;
    $is_transaction_active = false;

    try {
        if ($action === 'assign_driver' && $order_id) {
            $new_driver_id = $_POST['driver_id'] ?? '';
            if (!empty($new_driver_id)) {
                mysqli_begin_transaction($conn);
                $is_transaction_active = true;

                $sqlOld = "SELECT d.full_name FROM orders o JOIN drivers d ON o.driver_id = d.id WHERE o.id = ?";
                $stmtOld = mysqli_prepare($conn, $sqlOld);
                mysqli_stmt_bind_param($stmtOld, "i", $order_id);
                mysqli_stmt_execute($stmtOld);
                $resOld = mysqli_stmt_get_result($stmtOld);
                $old_driver_name = ($rowOld = mysqli_fetch_row($resOld)) ? $rowOld[0] : null;
                mysqli_stmt_close($stmtOld);

                $stmt1 = mysqli_prepare($conn, "UPDATE orders SET driver_id = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt1, "ii", $new_driver_id, $order_id);
                if (!mysqli_stmt_execute($stmt1))
                    throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt1);

                $stmt2 = mysqli_prepare($conn, "SELECT full_name FROM drivers WHERE id = ?");
                mysqli_stmt_bind_param($stmt2, "i", $new_driver_id);
                mysqli_stmt_execute($stmt2);
                $res2 = mysqli_stmt_get_result($stmt2);
                $new_driver_name = ($row2 = mysqli_fetch_row($res2)) ? $row2[0] : null;
                mysqli_stmt_close($stmt2);

                $log_msg = $old_driver_name
                    ? "Thay đổi tài xế: từ [$old_driver_name] sang [$new_driver_name]"
                    : "Điều phối viên gán tài xế: $new_driver_name";
                $stmt3 = mysqli_prepare($conn, "INSERT INTO order_logs (order_id, status, description) VALUES (?, 'pending', ?)");
                mysqli_stmt_bind_param($stmt3, "is", $order_id, $log_msg);
                if (!mysqli_stmt_execute($stmt3))
                    throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt3);

                mysqli_commit($conn);
                $is_transaction_active = false;
                $message = "<div class='alert alert-success shadow-sm'>Cập nhật tài xế <strong>$new_driver_name</strong> thành công!</div>";
            }
        } elseif ($action === 'unassign_driver' && $order_id) {
            mysqli_begin_transaction($conn);
            $is_transaction_active = true;

            $stmt1 = mysqli_prepare($conn, "UPDATE orders SET driver_id = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt1, "i", $order_id);
            if (!mysqli_stmt_execute($stmt1))
                throw new Exception(mysqli_error($conn));
            mysqli_stmt_close($stmt1);

            $log_msg = "Điều phối viên đã gỡ bỏ tài xế khỏi đơn hàng";
            $stmt2 = mysqli_prepare($conn, "INSERT INTO order_logs (order_id, status, description) VALUES (?, 'pending', ?)");
            mysqli_stmt_bind_param($stmt2, "is", $order_id, $log_msg);
            if (!mysqli_stmt_execute($stmt2))
                throw new Exception(mysqli_error($conn));
            mysqli_stmt_close($stmt2);

            mysqli_commit($conn);
            $is_transaction_active = false;
            $message = "<div class='alert alert-warning shadow-sm'>Đã gỡ bỏ tài xế thành công!</div>";
        } elseif ($action === 'update_status' && $order_id) {
            $new_status_val = trim($_POST['new_status'] ?? '');
            if (!empty($new_status_val)) {
                mysqli_begin_transaction($conn);
                $is_transaction_active = true;

                $stmt1 = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt1, "si", $new_status_val, $order_id);
                if (!mysqli_stmt_execute($stmt1))
                    throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt1);

                $log_msg = "Cập nhật trạng thái sang [" . strtoupper($new_status_val) . "]";
                $stmt2 = mysqli_prepare($conn, "INSERT INTO order_logs (order_id, status, description) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt2, "iss", $order_id, $new_status_val, $log_msg);
                if (!mysqli_stmt_execute($stmt2))
                    throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt2);

                mysqli_commit($conn);
                $is_transaction_active = false;
                $message = "<div class='alert alert-success shadow-sm'>Cập nhật trạng thái thành công!</div>";
            }
        } elseif ($action === 'verify_pod' && $order_id) {
            $pod_status = $_POST['pod_status'] ?? '';
            if (in_array($pod_status, ['verified', 'rejected'])) {
                mysqli_begin_transaction($conn);
                $is_transaction_active = true;

                $stmt1 = mysqli_prepare($conn, "UPDATE orders SET pod_status = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt1, "si", $pod_status, $order_id);
                if (!mysqli_stmt_execute($stmt1))
                    throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt1);

                $status_text = ($pod_status === 'verified') ? "ĐÃ DUYỆT (Hợp lệ)" : "TỪ CHỐI (Không hợp lệ)";
                $log_msg = "Điều phối viên đánh giá chứng từ POD: " . $status_text;
                $stmt2 = mysqli_prepare($conn, "INSERT INTO order_logs (order_id, status, description) VALUES (?, 'completed', ?)");
                mysqli_stmt_bind_param($stmt2, "is", $order_id, $log_msg);
                if (!mysqli_stmt_execute($stmt2))
                    throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt2);

                mysqli_commit($conn);
                $is_transaction_active = false;
                $message = "<div class='alert alert-success shadow-sm'>Đã lưu kết quả duyệt POD!</div>";
            }
        }
    } catch (Exception $e) {
        if ($is_transaction_active)
            mysqli_rollback($conn);
        $message = "<div class='alert alert-danger shadow-sm'>Lỗi hệ thống: " . $e->getMessage() . "</div>";
    }
}

// ==============================================================================
// PHẦN 2: TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ - GIỮ NGUYÊN LOGIC
// ==============================================================================
try {
    $limit = 9; // Giảm limit xuống để hợp với layout dashboard
    $page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int) $_GET['p'] : 1;
    $offset = ($page - 1) * $limit;

    $resCount = mysqli_query($conn, "SELECT COUNT(*) FROM orders");
    $total_orders = mysqli_fetch_row($resCount)[0];
    $total_pages = ceil($total_orders / $limit);

    $sql_all = "SELECT o.*, d.full_name as driver_name FROM orders o LEFT JOIN drivers d ON o.driver_id = d.id ORDER BY o.id ASC LIMIT ? OFFSET ?";
    $stmt_all = mysqli_prepare($conn, $sql_all);
    mysqli_stmt_bind_param($stmt_all, "ii", $limit, $offset);
    mysqli_stmt_execute($stmt_all);
    $res_all = mysqli_stmt_get_result($stmt_all);
    $all_orders = mysqli_fetch_all($res_all, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_all);

    $res_drivers = mysqli_query($conn, "SELECT id, full_name, phone FROM drivers WHERE work_status = 'active'");
    $available_drivers = mysqli_fetch_all($res_drivers, MYSQLI_ASSOC);

    $selected_order = null;
    $pod_document = null;
    $order_logs = [];

    $selected_id = $_GET['order_id'] ?? ($_POST['order_id'] ?? null);
    if ($selected_id) {
        $sql_single = "SELECT o.*, d.full_name as driver_name FROM orders o LEFT JOIN drivers d ON o.driver_id = d.id WHERE o.id = ?";
        $stmt_single = mysqli_prepare($conn, $sql_single);
        mysqli_stmt_bind_param($stmt_single, "i", $selected_id);
        mysqli_stmt_execute($stmt_single);
        $res_single = mysqli_stmt_get_result($stmt_single);
        $selected_order = mysqli_fetch_assoc($res_single);
        mysqli_stmt_close($stmt_single);

        $sql_pod = "SELECT file_url FROM order_documents WHERE order_id = ? AND type = 'pod' ORDER BY id ASC LIMIT 1";
        $stmt_pod = mysqli_prepare($conn, $sql_pod);
        mysqli_stmt_bind_param($stmt_pod, "i", $selected_id);
        mysqli_stmt_execute($stmt_pod);
        $res_pod = mysqli_stmt_get_result($stmt_pod);
        $pod_document = mysqli_fetch_assoc($res_pod);
        mysqli_stmt_close($stmt_pod);

        $sql_logs = "SELECT description, created_at FROM order_logs WHERE order_id = ? ORDER BY created_at DESC";
        $stmt_logs = mysqli_prepare($conn, $sql_logs);
        mysqli_stmt_bind_param($stmt_logs, "i", $selected_id);
        mysqli_stmt_execute($stmt_logs);
        $res_logs = mysqli_stmt_get_result($stmt_logs);
        $order_logs = mysqli_fetch_all($res_logs, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_logs);
    }
} catch (Exception $e) {
    die("Lỗi CSDL: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Điều phối Logistics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../dashboard.css">
    <link rel="stylesheet" href="dispatch.css">
</head>

<body>

    <div id="main-wrapper">
        <div class="dashboard-container">

            <div class="dash-header clearfix">
                <div class="title-area">
                    <h1 class="page-title">Điều phối Đơn hàng</h1>
                    <p class="page-desc">Quản lý, theo dõi và điều phối đơn hàng, tài xế trên toàn hệ thống.</p>
                </div>
            </div>

            <?= $message ?>

            <div class="row" gx-3 style="margin: 0;">
                <div class="col-lg-8 mb-4">
                    <div class="col-lg-8 mb-4">
                        <div class="dash-card rounded-table-card h-100">
                            <div class="table-header border-0 pb-0 mb-2">
                                <h6 class="mb-0 text-bold-dark">Danh sách chờ xử lý</h6>
                            </div>

                            <div class="table-responsive">
                                <table class="modern-rounded-table w-100">
                                    <thead>
                                        <tr>
                                            <th class="ps-3">Mã đơn</th>
                                            <th>Trạng thái</th>
                                            <th>Tài xế phụ trách</th>
                                            <th class="text-end pe-3">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_orders as $row): ?>
                                            <tr class="<?= ($selected_id == $row['id']) ? 'active-row' : '' ?>">
                                                <td class="text-bold-dark ps-3">
                                                    <?= htmlspecialchars($row['tracking_code']) ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = 'bg-secondary';
                                                    if ($row['status'] == 'pending')
                                                        $badge_class = 'bg-warning text-dark';
                                                    if ($row['status'] == 'delivering')
                                                        $badge_class = 'bg-primary';
                                                    if ($row['status'] == 'delivered')
                                                        $badge_class = 'bg-success';
                                                    ?>
                                                    <span
                                                        class="badge <?= $badge_class ?> rounded-pill px-3 py-2"><?= strtoupper($row['status']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <small
                                                            class="fw-bold"><?= $row['driver_name'] ?? '<span class="text-muted fst-italic">Chưa gán</span>' ?></small>
                                                        <small class="text-muted" style="font-size: 11px;">Cập nhật:
                                                            <?= date('d/m/Y', strtotime($row['created_at'] ?? 'now')) ?></small>
                                                    </div>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <a href="?order_id=<?= $row['id'] ?>&p=<?= $page ?>"
                                                        class="btn-detail-action text-decoration-none">
                                                        <span>Chi tiết</span>
                                                        <i class="bi bi-chevron-right ms-1"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-footer d-flex justify-content-between align-items-center mt-3 pt-3">
                                <span class="text-muted small">
                                    Hiển thị trang <strong class="text-dark"><?= $page ?></strong> / <strong
                                        class="text-dark"><?= $total_pages ?></strong>
                                </span>

                                <div class="pagination-mini">
                                    <a href="?p=<?= max(1, $page - 1) ?>"
                                        class="btn-page text-decoration-none <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <i class="bi bi-chevron-left"></i> Trước
                                    </a>
                                    <span class="page-num"><?= $page ?></span>
                                    <a href="?p=<?= min($total_pages, $page + 1) ?>"
                                        class="btn-page text-decoration-none <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                        Sau <i class="bi bi-chevron-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="sticky-top-card">
                        <?php if ($selected_order): ?>
                            <div class="dash-card detail-card border-top border-primary border-3">

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h6 class="mb-0 text-bold-dark">
                                        <i class="bi bi-info-circle-fill text-primary me-1"></i> Chi tiết:
                                        <?= htmlspecialchars($selected_order['tracking_code']) ?>
                                    </h6>
                                    <a href="print_label.php?id=<?= $selected_order['id'] ?>" target="_blank"
                                        class="btn-print-action text-decoration-none">
                                        <i class="bi bi-upc-scan"></i> In tem
                                    </a>
                                </div>

                                <div class="row mb-3 g-3">
                                    <div class="col-6">
                                        <label class="text-muted small fw-medium mb-1">Lấy hàng từ:</label>
                                        <p class="mb-0 small text-bold-dark">
                                            <?= htmlspecialchars($selected_order['source_text'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="col-6">
                                        <label class="text-muted small fw-medium mb-1">Giao hàng đến:</label>
                                        <p class="mb-0 small text-bold-dark">
                                            <?= htmlspecialchars($selected_order['dest_text'] ?? 'N/A') ?></p>
                                    </div>
                                </div>

                                <div class="insight-box d-flex justify-content-between mb-3">
                                    <div><span class="text-muted small">Loại:</span> <strong
                                            class="text-bold-dark"><?= htmlspecialchars($selected_order['goods_type'] ?? 'N/A') ?></strong>
                                    </div>
                                    <div><span class="text-muted small">Khối lượng:</span> <strong
                                            class="text-bold-dark"><?= htmlspecialchars($selected_order['weight'] ?? 0) ?>
                                            kg</strong></div>
                                </div>

                                <div
                                    class="financial-summary-box d-flex justify-content-between align-items-center p-3 mb-4">
                                    <span class="small text-muted fw-medium">Phí ship:
                                        <?= number_format($selected_order['shipping_fee'] ?? 0) ?>đ</span>
                                    <span class="text-primary fw-bold" style="font-size: 15px;">COD:
                                        <?= number_format($selected_order['cod_amount'] ?? 0) ?>đ</span>
                                </div>

                                <div class="mb-4">
                                    <?php if (empty($selected_order['driver_id'])): ?>
                                        <h6 class="insight-title">GÁN TÀI XẾ</h6>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="assign_driver">
                                            <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                            <div class="input-group">
                                                <select name="driver_id" class="form-select border-primary shadow-none"
                                                    required>
                                                    <option value="">-- Chọn tài xế --</option>
                                                    <?php foreach ($available_drivers as $d): ?>
                                                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?>
                                                            (<?= htmlspecialchars($d['phone']) ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary"
                                                    style="background: #4318ff; border: none;">Phân công</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="insight-card driver-active-card p-3 rounded">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="bi bi-person-badge-fill fs-3 text-success me-3"></i>
                                                <div>
                                                    <small class="text-muted d-block mb-1">Tài xế đang phụ trách:</small>
                                                    <strong
                                                        class="text-dark fs-6"><?= htmlspecialchars($selected_order['driver_name']) ?></strong>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary w-50" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#boxChange">Đổi tài xế</button>
                                                <form method="POST" class="w-50"
                                                    onsubmit="return confirm('Chắc chắn muốn gỡ tài xế này?');">
                                                    <input type="hidden" name="action" value="unassign_driver">
                                                    <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">Bỏ
                                                        gán</button>
                                                </form>
                                            </div>
                                            <div class="collapse mt-3" id="boxChange">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="assign_driver">
                                                    <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                                    <div class="input-group input-group-sm">
                                                        <select name="driver_id" class="form-select shadow-none" required>
                                                            <option value="">-- Chọn tài xế mới --</option>
                                                            <?php foreach ($available_drivers as $d): ?>
                                                                <?php if ($d['id'] != $selected_order['driver_id']): ?>
                                                                    <option value="<?= $d['id'] ?>">
                                                                        <?= htmlspecialchars($d['full_name']) ?></option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="btn btn-primary"
                                                            style="background: #4318ff;">Lưu</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="row pt-4 border-top g-3">
                                    <div class="col-6">
                                        <h6 class="insight-title">TRẠNG THÁI</h6>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                            <select name="new_status" class="form-select form-select-sm shadow-none mb-2"
                                                required>
                                                <option value="pending" <?= $selected_order['status'] == 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                                <option value="picking" <?= $selected_order['status'] == 'picking' ? 'selected' : '' ?>>Đang lấy hàng</option>
                                                <option value="at_hub" <?= $selected_order['status'] == 'at_hub' ? 'selected' : '' ?>>Đã lưu kho</option>
                                                <option value="delivering" <?= $selected_order['status'] == 'delivering' ? 'selected' : '' ?>>Đang giao</option>
                                                <option value="delivered" <?= $selected_order['status'] == 'delivered' ? 'selected' : '' ?>>Đã giao xong</option>
                                                <option value="cancelled" <?= $selected_order['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                            </select>
                                            <button type="submit" class="btn btn-dark btn-sm w-100">Cập nhật</button>
                                        </form>
                                    </div>

                                    <div class="col-6 border-start ps-3">
                                        <h6 class="insight-title">CHỨNG TỪ (POD)</h6>
                                        <?php if ($pod_document && !empty($pod_document['file_url'])): ?>
                                            <form method="POST" class="d-flex flex-column gap-2 mt-2">
                                                <input type="hidden" name="action" value="verify_pod">
                                                <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                                <button type="submit" name="pod_status" value="verified"
                                                    class="btn btn-success btn-sm" <?= ($selected_order['pod_status'] ?? '') == 'verified' ? 'disabled' : '' ?>>Duyệt (Hợp lệ)</button>
                                                <button type="submit" name="pod_status" value="rejected"
                                                    class="btn btn-danger btn-sm" <?= ($selected_order['pod_status'] ?? '') == 'rejected' ? 'disabled' : '' ?>>Từ chối</button>
                                            </form>
                                        <?php else: ?>
                                            <div
                                                class="text-muted small text-center mt-3 bg-light p-2 rounded border border-dashed">
                                                <i class="bi bi-image d-block mb-1 fs-5"></i> Chưa có POD</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mt-4 pt-4 border-top">
                                    <h6 class="insight-title"><i class="bi bi-clock-history me-1"></i> LỊCH SỬ DI CHUYỂN
                                    </h6>
                                    <div class="timeline-box pe-2" style="max-height: 200px; overflow-y: auto;">
                                        <?php if (!empty($order_logs)): ?>
                                            <ul class="list-group list-group-flush small timeline-list mt-2">
                                                <?php foreach ($order_logs as $log): ?>
                                                    <li class="list-group-item">
                                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">
                                                            <i class="bi bi-calendar2-event"></i>
                                                            <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                                        </div>
                                                        <div class="fw-medium text-dark lh-sm">
                                                            <?= htmlspecialchars($log['description']) ?></div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted small mb-0 mt-2 bg-light p-2 rounded">Chưa ghi nhận lịch sử
                                                nào.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        <?php else: ?>
                            <div class="dash-card detail-card d-flex flex-column align-items-center justify-content-center text-muted"
                                style="height: 400px; border: 2px dashed #e2e8f0;">
                                <div class="bg-light rounded-circle p-4 mb-3">
                                    <i class="bi bi-truck fs-1 text-primary"></i>
                                </div>
                                <h6 class="text-dark">Chưa chọn đơn hàng</h6>
                                <p class="mb-0 small">Chọn một đơn hàng bên trái để xem chi tiết và điều phối.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php disconnectDB(); ?>