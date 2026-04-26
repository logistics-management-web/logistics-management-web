<?php
session_start();
require_once "../../../config/db.php"; 

// Khởi tạo kết nối DB
connectDB();
global $conn;

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login/login.php");
    exit;
}

$dispatcher_name = $_SESSION["username"] ?? 'Unknown';
$message = "";

// ==============================================================================
// PHẦN 1: XỬ LÝ DỮ LIỆU POST (THAO TÁC)
// ==============================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $order_id = $_POST['order_id'] ?? null;
    $is_transaction_active = false; 

    try {
        // --- HÀNH ĐỘNG 1 & 2: GÁN HOẶC THAY ĐỔI TÀI XẾ ---
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
                if (!mysqli_stmt_execute($stmt1)) throw new Exception(mysqli_error($conn));
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
                if (!mysqli_stmt_execute($stmt3)) throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt3);

                mysqli_commit($conn);
                $is_transaction_active = false;
                $message = "<div class='alert alert-success shadow-sm'>Cập nhật tài xế <strong>$new_driver_name</strong> thành công!</div>";
            }
        }

        // --- HÀNH ĐỘNG 3: BỎ GÁN TÀI XẾ ---
        elseif ($action === 'unassign_driver' && $order_id) {
            mysqli_begin_transaction($conn);
            $is_transaction_active = true;
            
            $stmt1 = mysqli_prepare($conn, "UPDATE orders SET driver_id = NULL WHERE id = ?");
            mysqli_stmt_bind_param($stmt1, "i", $order_id);
            if (!mysqli_stmt_execute($stmt1)) throw new Exception(mysqli_error($conn));
            mysqli_stmt_close($stmt1);

            $log_msg = "Điều phối viên đã gỡ bỏ tài xế khỏi đơn hàng";
            $stmt2 = mysqli_prepare($conn, "INSERT INTO order_logs (order_id, status, description) VALUES (?, 'pending', ?)");
            mysqli_stmt_bind_param($stmt2, "is", $order_id, $log_msg);
            if (!mysqli_stmt_execute($stmt2)) throw new Exception(mysqli_error($conn));
            mysqli_stmt_close($stmt2);

            mysqli_commit($conn);
            $is_transaction_active = false;
            $message = "<div class='alert alert-warning shadow-sm'>Đã gỡ bỏ tài xế thành công!</div>";
        }

        // --- HÀNH ĐỘNG 4: CẬP NHẬT TRẠNG THÁI THỦ CÔNG ---
        elseif ($action === 'update_status' && $order_id) {
            $new_status_val = trim($_POST['new_status'] ?? '');
            if (!empty($new_status_val)) {
                mysqli_begin_transaction($conn);
                $is_transaction_active = true;

                $stmt1 = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt1, "si", $new_status_val, $order_id);
                if (!mysqli_stmt_execute($stmt1)) throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt1);

                $log_msg = "Cập nhật trạng thái sang [" . strtoupper($new_status_val) . "]";
                $stmt2 = mysqli_prepare($conn, "INSERT INTO order_logs (order_id, status, description) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt2, "iss", $order_id, $new_status_val, $log_msg);
                if (!mysqli_stmt_execute($stmt2)) throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt2);

                mysqli_commit($conn);
                $is_transaction_active = false;
                $message = "<div class='alert alert-success shadow-sm'>Cập nhật trạng thái thành công!</div>";
            }
        }

        // --- HÀNH ĐỘNG 5: KIỂM DUYỆT CHỨNG TỪ POD ---
        elseif ($action === 'verify_pod' && $order_id) {
            $pod_status = $_POST['pod_status'] ?? '';
            if (in_array($pod_status, ['verified', 'rejected'])) {
                mysqli_begin_transaction($conn);
                $is_transaction_active = true;

                $stmt1 = mysqli_prepare($conn, "UPDATE orders SET pod_status = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt1, "si", $pod_status, $order_id);
                if (!mysqli_stmt_execute($stmt1)) throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt1);

                $status_text = ($pod_status === 'verified') ? "ĐÃ DUYỆT (Hợp lệ)" : "TỪ CHỐI (Không hợp lệ)";
                $log_msg = "Điều phối viên đánh giá chứng từ POD: " . $status_text;
                $stmt2 = mysqli_prepare($conn, "INSERT INTO order_logs (order_id, status, description) VALUES (?, 'completed', ?)");
                mysqli_stmt_bind_param($stmt2, "is", $order_id, $log_msg);
                if (!mysqli_stmt_execute($stmt2)) throw new Exception(mysqli_error($conn));
                mysqli_stmt_close($stmt2);

                mysqli_commit($conn);
                $is_transaction_active = false;
                $message = "<div class='alert alert-success shadow-sm'>Đã lưu kết quả duyệt POD!</div>";
            }
        }
    } catch (Exception $e) {
        if ($is_transaction_active) mysqli_rollback($conn);
        $message = "<div class='alert alert-danger shadow-sm'>Lỗi hệ thống: " . $e->getMessage() . "</div>";
    }
}

// ==============================================================================
// PHẦN 2: TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ
// ==============================================================================
try {
    $limit = 25;
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
        // Lấy chi tiết đơn
        $sql_single = "SELECT o.*, d.full_name as driver_name FROM orders o LEFT JOIN drivers d ON o.driver_id = d.id WHERE o.id = ?";
        $stmt_single = mysqli_prepare($conn, $sql_single);
        mysqli_stmt_bind_param($stmt_single, "i", $selected_id);
        mysqli_stmt_execute($stmt_single);
        $res_single = mysqli_stmt_get_result($stmt_single);
        $selected_order = mysqli_fetch_assoc($res_single);
        mysqli_stmt_close($stmt_single);

        // Lấy ảnh POD
        $sql_pod = "SELECT file_url FROM order_documents WHERE order_id = ? AND type = 'pod' ORDER BY id ASC LIMIT 1";
        $stmt_pod = mysqli_prepare($conn, $sql_pod);
        mysqli_stmt_bind_param($stmt_pod, "i", $selected_id);
        mysqli_stmt_execute($stmt_pod);
        $res_pod = mysqli_stmt_get_result($stmt_pod);
        $pod_document = mysqli_fetch_assoc($res_pod);
        mysqli_stmt_close($stmt_pod);

        // Lấy Timeline
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
    <style>
        body { background-color: #f8f9fa; }
        .table-hover tbody tr:hover { cursor: pointer; }
        .sticky-top-card { position: sticky; top: 20px; }
        .border-bottom-dashed { border-bottom: 1px dashed #dee2e6; }
        .timeline-box::-webkit-scrollbar { width: 6px; }
        .timeline-box::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 4px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container-fluid px-4">
            <span class="navbar-brand"><i class="bi bi-truck"></i> Hệ Thống Điều Phối</span>
            <span class="text-white small">Chào, <strong><?= htmlspecialchars($dispatcher_name) ?></strong> | <a href="../../login/logout.php" class="text-danger text-decoration-none">Thoát</a></span>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        <?= $message ?>
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Trạng thái</th>
                                    <th>Tài xế phụ trách</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_orders as $row): ?>
                                <tr class="<?= ($selected_id == $row['id']) ? 'table-primary' : '' ?>">
                                    <td><small><strong><?= htmlspecialchars($row['tracking_code']) ?></strong></small></td>
                                    <td><span class="badge bg-secondary"><?= strtoupper($row['status']) ?></span></td>
                                    <td><small><?= $row['driver_name'] ?? '<span class="text-muted fst-italic">Chưa gán</span>' ?></small></td>
                                    <td class="text-center">
                                        <a href="?order_id=<?= $row['id'] ?>&p=<?= $page ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-right"></i> Chi tiết</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white border-top-0 py-3">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?p=<?= $page - 1 ?>">Trước</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?p=<?= $page + 1 ?>">Sau</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="sticky-top-card">
                    <?php if ($selected_order): ?>
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Chi tiết: <?= htmlspecialchars($selected_order['tracking_code']) ?></h6>
                            <a href="print_label.php?id=<?= $selected_order['id'] ?>" target="_blank" class="btn btn-sm btn-light text-primary fw-bold">
                                <i class="bi bi-upc-scan"></i> In tem
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="text-muted small">Lấy hàng từ:</label>
                                    <p class="mb-0 small"><strong><?= htmlspecialchars($selected_order['source_text'] ?? 'N/A') ?></strong></p>
                                </div>
                                <div class="col-6">
                                    <label class="text-muted small">Giao hàng đến:</label>
                                    <p class="mb-0 small"><strong><?= htmlspecialchars($selected_order['dest_text'] ?? 'N/A') ?></strong></p>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span>Loại hàng: <strong><?= htmlspecialchars($selected_order['goods_type'] ?? 'N/A') ?></strong></span>
                                <span>Khối lượng: <strong><?= htmlspecialchars($selected_order['weight'] ?? 0) ?> kg</strong></span>
                            </div>

                            <?php if (!empty($selected_order['note'])): ?>
                            <div class="alert alert-warning py-2 border-0 mb-2 small">
                                <i class="bi bi-pencil-square"></i> <strong>Ghi chú:</strong> <?= htmlspecialchars($selected_order['note']) ?>
                            </div>
                            <?php endif; ?>
                            <div class="alert alert-secondary py-2 border-0 mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small">Phí ship: <?= number_format($selected_order['shipping_fee'] ?? 0) ?>đ</span>
                                    <span class="text-primary fw-bold">COD: <?= number_format($selected_order['cod_amount'] ?? 0) ?>đ</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <?php if (empty($selected_order['driver_id'])): ?>
                                    <h6 class="text-primary small fw-bold mb-2">GÁN TÀI XẾ</h6>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="assign_driver">
                                        <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                        <div class="input-group">
                                            <select name="driver_id" class="form-select border-primary shadow-none" required>
                                                <option value="">-- Chọn tài xế --</option>
                                                <?php foreach ($available_drivers as $d): ?>
                                                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?> (<?= htmlspecialchars($d['phone']) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary">Phân công</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="p-3 bg-light border rounded">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-person-badge fs-3 text-success me-3"></i>
                                            <div>
                                                <small class="text-muted d-block">Tài xế đang phụ trách:</small>
                                                <strong class="text-success"><?= htmlspecialchars($selected_order['driver_name']) ?></strong>
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <button class="btn btn-sm btn-outline-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#boxChange">
                                                    Thay đổi
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn gỡ tài xế này khỏi đơn hàng?');">
                                                    <input type="hidden" name="action" value="unassign_driver">
                                                    <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">Bỏ gán</button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="collapse mt-3" id="boxChange">
                                            <form method="POST" class="p-2 border rounded bg-white">
                                                <input type="hidden" name="action" value="assign_driver">
                                                <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                                <div class="input-group input-group-sm">
                                                    <select name="driver_id" class="form-select shadow-none" required>
                                                        <option value="">-- Chọn tài xế mới --</option>
                                                        <?php foreach ($available_drivers as $d): ?>
                                                            <?php if($d['id'] != $selected_order['driver_id']): ?>
                                                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['full_name']) ?></option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-primary">Xác nhận</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4 pt-3 border-top">
                                <h6 class="text-dark small fw-bold mb-2">CẬP NHẬT TRẠNG THÁI</h6>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                    <select name="new_status" class="form-select form-select-sm shadow-none" required>
                                        <option value="pending" <?= $selected_order['status']=='pending'?'selected':'' ?>>PENDING (Chờ xử lý)</option>
                                        <option value="picking" <?= $selected_order['status']=='picking'?'selected':'' ?>>PICKING (Đang lấy hàng)</option>
                                        <option value="at_hub" <?= $selected_order['status']=='at_hub'?'selected':'' ?>>AT HUB (Đã lưu kho)</option>
                                        <option value="delivering" <?= $selected_order['status']=='delivering'?'selected':'' ?>>DELIVERING (Đang giao)</option>
                                        <option value="delivered" <?= $selected_order['status']=='delivered'?'selected':'' ?>>DELIVERED (Đã giao xong)</option>
                                        <option value="cancelled" <?= $selected_order['status']=='cancelled'?'selected':'' ?>>CANCELLED (Đã hủy)</option>
                                        <option value="returning" <?= $selected_order['status']=='returning'?'selected':'' ?>>RETURNING (Đang hoàn trả)</option>
                                        <option value="returned" <?= $selected_order['status']=='returned'?'selected':'' ?>>RETURNED (Đã hoàn trả)</option>
                                    </select>
                                    <button type="submit" class="btn btn-dark btn-sm px-3">Lưu</button>
                                </form>
                            </div>

                            <div class="mb-4 pt-3 border-top">
                                <h6 class="text-dark small fw-bold mb-3"><i class="bi bi-file-earmark-image"></i> CHỨNG TỪ GIAO NHẬN (POD)</h6>
                                <?php if ($pod_document && !empty($pod_document['file_url'])): ?>
                                    <div class="text-center mb-2 bg-light p-2 border rounded">
                                        <img src="../../../<?= htmlspecialchars($pod_document['file_url']) ?>" class="img-fluid rounded border" alt="Hình ảnh POD" style="max-height: 200px; object-fit: contain;">
                                    </div>
                                    <form method="POST" class="d-flex gap-2 mt-3">
                                        <input type="hidden" name="action" value="verify_pod">
                                        <input type="hidden" name="order_id" value="<?= $selected_order['id'] ?>">
                                        <button type="submit" name="pod_status" value="verified" class="btn btn-success btn-sm w-50" <?= ($selected_order['pod_status'] ?? '') == 'verified' ? 'disabled' : '' ?>>
                                            <i class="bi bi-check-circle"></i> Duyệt (Hợp lệ)
                                        </button>
                                        <button type="submit" name="pod_status" value="rejected" class="btn btn-danger btn-sm w-50" <?= ($selected_order['pod_status'] ?? '') == 'rejected' ? 'disabled' : '' ?>>
                                            <i class="bi bi-x-circle"></i> Từ chối
                                        </button>
                                    </form>
                                    <?php if(!empty($selected_order['pod_status']) && $selected_order['pod_status'] != 'pending'): ?>
                                        <div class="text-center mt-2 small">
                                            Trạng thái duyệt: <strong class="<?= $selected_order['pod_status'] == 'verified' ? 'text-success' : 'text-danger' ?>"><?= strtoupper($selected_order['pod_status']) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning py-2 small mb-0 text-center"><i class="bi bi-exclamation-triangle"></i> Đơn hàng chưa có chứng từ POD.</div>
                                <?php endif; ?>
                            </div>

                            <div class="pt-3 border-top">
                                <h6 class="text-dark small fw-bold mb-3"><i class="bi bi-clock-history"></i> LỊCH SỬ DI CHUYỂN (TIMELINE)</h6>
                                <div class="timeline-box pe-2" style="max-height: 250px; overflow-y: auto;">
                                    <?php if (!empty($order_logs)): ?>
                                        <ul class="list-group list-group-flush small">
                                            <?php foreach ($order_logs as $log): ?>
                                            <li class="list-group-item px-0 py-2 border-bottom-dashed">
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    <i class="bi bi-calendar2-event"></i> <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                                </div>
                                                <div class="fw-medium text-dark"><?= htmlspecialchars($log['description']) ?></div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted small mb-0">Chưa ghi nhận lịch sử nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center p-5 border rounded bg-white text-muted shadow-sm d-flex flex-column align-items-center justify-content-center" style="height: 400px;">
                        <i class="bi bi-truck fs-1 mb-3 text-secondary"></i>
                        <p class="mb-0">Vui lòng chọn một đơn hàng từ danh sách bên trái để thực hiện điều phối.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php disconnectDB(); ?>