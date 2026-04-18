<?php
require "../../config/db.php";
connectDB();
$sql = "SELECT * FROM hubs";
$result = mysqli_query($conn, $sql);
echo "<h2>Danh sách kho bãi (Test kết nối)</h2>";

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Mã Kho</th><th>Tên Kho</th><th>Khu vực</th><th>Trạng thái</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['is_active'] ? "Hoạt động" : "Đã đóng";
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['code'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['region'] . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Kết nối thành công nhưng chưa có dữ liệu trong bảng 'hubs' hoặc lỗi truy vấn: " . mysqli_error($conn);
}
?>