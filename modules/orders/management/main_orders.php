<?php
session_start();

// Kiểm tra nếu chưa đăng nhập thì đẩy về trang đăng nhập
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../../login/login.php");
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LogisCore - Quản lý Đơn hàng</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="main_orders.css" />
    <link rel="stylesheet" href="sub_orders_overview.css" />
    <link rel="stylesheet" href="sub_orders_timeline.css" />
    <link rel="stylesheet" href="sub_orders_chungtu.css" />
    <link rel="stylesheet" href="sub_order_logs.css" />
    <link rel="stylesheet" href="sub_order_create.css" />
    <link rel="stylesheet" href="../../../dashboard/dashboard.css" />
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">MD Logistic</div>

    <div class="menu-title">VẬN HÀNH</div>
    <a href="#" class="nav-item">
        <i class="fa-solid fa-chart-pie"></i> Dashboard Vận hành
    </a>

    <a href="#" class="nav-item active">
        <i class="fa-solid fa-clipboard-list"></i> Quản lý Đơn hàng
    </a>

    <a href="#" class="nav-item">
        <i class="fa-solid fa-network-wired fa-coordination"></i> Điều phối Đơn hàng
    </a>

    <div class="menu-title">HỆ THỐNG</div>

    <a href="#" class="nav-item">
        <i class="fa-solid fa-warehouse"></i> Quản trị Kho bãi
    </a>

    <!-- Dropdown Xe tải -->
    <div class="nav-group">
        <a href="#" class="nav-item" id="menu-trucks-main">
            <i class="fa-solid fa-truck"></i> Quản trị Xe tải
            <i class="fa-solid fa-chevron-down dropdown-icon" 
               style="float: right; margin-top: 4px; font-size: 12px;"></i>
        </a>

        <ul class="sub-menu" id="submenu-trucks" style="display: none;">
            <li><a href="#" class="sub-nav-item" data-src="../../trucks/truck-index.php">Danh mục Xe tải</a></li>
            <li><a href="#" class="sub-nav-item" data-src="../../trucks/index.php">Danh mục Tài xế</a></li>
            <li><a href="#" class="sub-nav-item" data-src="../../trucks/operating-index.php">Nhật ký vận hành</a></li>
        </ul>
    </div>

    <a href="#" class="nav-item">
        <i class="fa-solid fa-file-invoice-dollar fa-pricing"></i> Bảng giá cước
    </a>

    <!-- USER PROFILE -->
    <div class="user-profile clearfix">
        <img src="https://inkythuatso.com/uploads/images/2021/12/logo-dai-hoc-hang-hai-inkythuatso-14-16-05-20.jpg" alt="Avatar" />

        <div class="user-info">
            <p class="text-bold">
                <?php echo htmlspecialchars($_SESSION["username"]); ?>
            </p>
            <p class="text-gray" style="font-size: 12px">
                <?php echo htmlspecialchars($_SESSION["email"]); ?>
            </p>
        </div>

        <a href="../../login/logout.php" class="logout-btn" title="Đăng xuất khỏi hệ thống">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- TOP HEADER -->
    <div class="top-header clearfix">

        <div class="header-actions">
            <i class="fa-regular fa-bell"></i>
            <img src="https://inkythuatso.com/uploads/images/2021/12/logo-dai-hoc-hang-hai-inkythuatso-14-16-05-20.jpg" alt="User"/>
        </div>
    </div>

    <div id="main-view">
        <div class="page-container">

            <div class="clearfix">
                <div class="page-title-area">
                    <h1>Quản lý Đơn hàng</h1>
                    <p class="text-gray">
                        Quản lý, theo dõi và điều phối đơn hàng trên toàn hệ thống.
                    </p>
                </div>

                <div class="page-actions">
                    <button class="btn btn-outline">
                        <i class="fa-solid fa-download"></i> Xuất file
                    </button>
                    <button class="btn btn-primary" id="btn-create-order">
                        <i class="fa-solid fa-plus"></i> Tạo đơn mới
                    </button>
                </div>
            </div>

            <!-- STATS -->
            <div class="stats-row clearfix">
                <div class="col-4">
                    <div class="stat-card">
                        <span class="text-gray text-bold">Tổng đơn hàng</span>
                        <span class="badge badge-green">↗ 5.2%</span>
                        <div class="stat-value" id="stat-total">0</div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="stat-card">
                        <span class="text-gray text-bold">Đang giao</span>
                        <span class="badge badge-green">↗ 2.1%</span>
                        <div class="stat-value text-blue" id="stat-delivering">0</div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="stat-card">
                        <span class="text-gray text-bold">Chờ xử lý</span>
                        <span class="badge badge-orange">— 0.0%</span>
                        <div class="stat-value text-orange" id="stat-pending">0</div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="stat-card">
                        <span class="text-gray text-bold">Cảnh báo SLA</span>
                        <span class="badge badge-red">↘ 1.5%</span>
                        <div class="stat-value text-red" id="stat-sla">0</div>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-wrapper">
                <div class="clearfix">
                    <ul class="tabs" id="order-filter-tabs">
                        <li class="active" data-status="all">Tất cả</li>
                        <li data-status="pending">Chờ xử lý</li>
                        <li data-status="processing">Đang vận hành</li>
                        <li data-status="delivered">Hoàn thành</li>
                        <li data-status="issues">Sự cố</li>
                    </ul>

                    <div class="table-actions">
                        <button class="btn btn-outline" id="btn-open-filter">
                            <i class="fa-solid fa-filter"></i> Lọc nâng cao
                        </button>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" /></th>
                            <th>MÃ ĐƠN</th>
                            <th>KHÁCH HÀNG</th>
                            <th>TUYẾN ĐƯỜNG</th>
                            <th>TRẠNG THÁI</th>
                            <th>SLA (HẠN GIAO)</th>
                            <th>COD (VNĐ)</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="pagination clearfix">
                    <div class="page-info" id="page-info-text">
                        Hiển thị <b style="color:#2b3674">0</b> đến 
                        <b style="color:#2b3674">0</b> trong 
                        <b style="color:#2b3674">0</b> kết quả
                    </div>
                    <div class="page-controls" id="pagination-controls"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL FILTER -->
<div class="modal-overlay" id="filter-overlay"></div>
<div class="modal-box" id="filter-box" style="max-width: 500px;">
    <!-- giữ nguyên phần modal như cũ -->
</div>

<div id="sub-container"></div>
<div id="modal-create-container"></div>

<script src="orders.js"></script>
<script src="apps.js"></script>

</body>
</html>