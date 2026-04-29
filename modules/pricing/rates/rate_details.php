<?php
require "rates.php";
require "../routes/routes.php";
require "../tier/tier.php";

$rate_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$rate_info = rateGetById($rate_id); 
$rate_code = $rate_info['code_rates'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_add_route'])) routeAdd($_POST);
    elseif (isset($_POST['submit_edit_route'])) routeEdit($_POST);
    elseif (isset($_POST['submit_delete_route'])) routeDelete($_POST);
    if (isset($_POST['submit_add_tier'])) tierAdd($_POST);
    elseif (isset($_POST['submit_edit_tier'])) tierEdit($_POST);
    elseif (isset($_POST['submit_delete_tier'])) tierDelete($_POST);
    
    header("Location: rate_details.php?id=$rate_id");
    exit();
}

$routes = routeList($rate_id);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết Tuyến đường & Bậc giá</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<div class="main-content">
    <div class="page-container">
        <div class="page-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a href="../index.php" class="btn-back-square" title="Quay về danh sách">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            
            <div class="page-title-group">
                <nav class="breadcrumb-new"> 
                    <span>Mã: <strong><?php echo htmlspecialchars($rate_code); ?></strong></span>
                </nav>
                <h1 class="page-main-title">Cấu hình Tuyến đường & Bậc giá</h1>
            </div>
        </div>

        <button class="btn btn-primary btn-with-icon" onclick="openModal('addRouteModal')">
            <i class="fa-solid fa-plus"></i>
            <span>Thêm tuyến đường</span>
        </button>
    </div>

        <?php if (empty($routes)): ?>
            <div class="alert alert-info">Chưa có tuyến đường nào được cấu hình cho bảng giá này.</div>
        <?php else: ?>
            <?php foreach ($routes as $route): 
                $src_text = implode(", ", json_decode($route['source_regions'], true));
                $dst_text = implode(", ", json_decode($route['dest_regions'], true));
            ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i class="fa-solid fa-route text-blue"></i> 
                            <strong>Tuyến:</strong> <?php echo htmlspecialchars($src_text); ?> ⇄ <?php echo htmlspecialchars($dst_text); ?>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="status-badge status-active">
                                Cơ bản (<?php echo $route['base_weight']; ?>kg): <?php echo number_format($route['base_price']); ?> VNĐ
                            </span>
                            
                            <button class="btn btn-sm btn-edit btn-edit-route btn-secondary" 
                                    data-routeid="<?php echo $route['id']; ?>"
                                    data-src="<?php echo htmlspecialchars($src_text); ?>"
                                    data-dst="<?php echo htmlspecialchars($dst_text); ?>"
                                    data-bw="<?php echo $route['base_weight']; ?>"
                                    data-bp="<?php echo $route['base_price']; ?>">Sửa</button>

                            <button type="button" class="btn btn-sm btn-danger btn-delete-route" 
                                data-routeid="<?php echo $route['id']; ?>"
                                data-name="<?php echo htmlspecialchars($src_text . ' ⇄ ' . $dst_text); ?>">
                            Xóa
                        </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h6 style="margin-top: 0; margin-bottom: 16px; color: #8f9bba; font-size: 14px;">Cấu hình Bậc giá vượt mức:</h6>
                        <table style="margin-bottom: 24px;">
                            <thead>
                                <tr>
                                    <th>Từ (kg)</th>
                                    <th>Đến (kg)</th>
                                    <th>Bước nhảy (kg)</th>
                                    <th>Giá/Bước (VNĐ)</th>
                                    <th style="text-align: right;">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $tiers = tierList($route['id']);
                                foreach ($tiers as $tier): 
                                ?>
                                <tr>
                                    <td><?php echo $tier['from_weight']; ?></td>
                                    <td><?php echo $tier['to_weight'] ?? 'Max'; ?></td>
                                    <td><?php echo $tier['step_weight']; ?></td>
                                    <td class="text-blue"><strong><?php echo number_format($tier['step_price']); ?></strong></td>
                                    <td style="text-align: right;" class="d-flex justify-content-end align-items-center gap-2">
                                        <button class="btn btn-sm btn-edit btn-edit-tier btn-outline" 
                                                data-tierid="<?php echo $tier['id']; ?>"
                                                data-from="<?php echo $tier['from_weight']; ?>"
                                                data-to="<?php echo $tier['to_weight']; ?>"
                                                data-stepw="<?php echo $tier['step_weight']; ?>"
                                                data-stepp="<?php echo $tier['step_price']; ?>">Sửa</button>
                                        <?php
                                        $tier_name = 'từ ' . $tier['from_weight'] . 'kg' . (!empty($tier['to_weight']) ? ' đến ' . $tier['to_weight'] . 'kg' : ' trở lên');
                                        ?>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-tier" 
                                                data-tierid="<?php echo $tier['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($tier_name); ?>">
                                            Xóa
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($tiers)): ?>
                                    <tr><td colspan="5" align="center" class="text-muted" style="padding: 24px;">Chưa có bậc giá vượt mức nào được cấu hình.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <?php 
                        $next_from_weight = 0; $is_max_reached = false;
                        if (!empty($tiers)) {
                            $last_tier = end($tiers); 
                            if (empty($last_tier['to_weight'])) $is_max_reached = true; 
                            else $next_from_weight = $last_tier['to_weight']; 
                        }
                        ?>
                        <div>
                            <?php if (!$is_max_reached): ?>
                                <button class="btn btn-sm btn-secondary btn-add-tier" 
                                        data-routeid="<?php echo $route['id']; ?>"
                                        data-nextfrom="<?php echo $next_from_weight; ?>">+ Thêm bậc giá mới</button>
                            <?php else: ?>
                                <span class="text-danger text-muted">Tuyến đường này đã cấu hình đến mức Max.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../routes/route-add.php'; ?>
<?php include '../routes/route-edit.php'; ?>
<?php include '../routes/route-delete.php'; ?>
<?php include '../tier/tier-add.php'; ?>
<?php include '../tier/tier-edit.php'; ?>
<?php include '../tier/tier-delete.php'; ?>

<script>
    function openModal(id) { document.getElementById(id).classList.add('show'); }
    function closeModal(id) { document.getElementById(id).classList.remove('show'); }

    // Đổ dữ liệu sửa Route
    document.querySelectorAll('.btn-edit-route').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_route_id').value = this.getAttribute('data-routeid');
            document.getElementById('edit_source_regions').value = this.getAttribute('data-src');
            document.getElementById('edit_dest_regions').value = this.getAttribute('data-dst');
            document.getElementById('edit_base_weight').value = this.getAttribute('data-bw');
            document.getElementById('edit_base_price').value = this.getAttribute('data-bp');
            openModal('editRouteModal');
        });
    });
    
    document.querySelectorAll('.btn-delete-route').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_route_id_input').value = this.getAttribute('data-routeid');
            document.getElementById('delete_route_name').textContent = this.getAttribute('data-name');
            openModal('deleteRouteModal');
        });
    });

    // Thêm Tier (lấy từ Data Attribute)
    document.querySelectorAll('.btn-add-tier').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('route_id_input').value = this.getAttribute('data-routeid');
            document.getElementById('from_weight_input').value = this.getAttribute('data-nextfrom');
            openModal('addTierModal');
        });
    });

    // Đổ dữ liệu sửa Tier
    document.querySelectorAll('.btn-edit-tier').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_tier_id').value = this.getAttribute('data-tierid');
            document.getElementById('edit_tier_from').value = this.getAttribute('data-from');
            document.getElementById('edit_tier_to').value = this.getAttribute('data-to');
            document.getElementById('edit_tier_step_w').value = this.getAttribute('data-stepw');
            document.getElementById('edit_tier_step_p').value = this.getAttribute('data-stepp');
            openModal('editTierModal');
        });
    });

    document.querySelectorAll('.btn-delete-tier').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_tier_id_input').value = this.getAttribute('data-tierid');
            document.getElementById('delete_tier_name').textContent = this.getAttribute('data-name');
            openModal('deleteTierModal');
        });
    });
</script>
</body>
</html>