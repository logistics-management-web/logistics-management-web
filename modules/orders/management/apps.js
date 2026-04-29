const menuDashboard = document.querySelector(
  "a.nav-item i.fa-chart-pie",
)?.parentElement;
const menuOrders = document.querySelector(
  "a.nav-item i.fa-clipboard-list",
)?.parentElement;

const menuCoordination = document.querySelector(
  "a.nav-item i.fa-coordination",
)?.parentElement;

const menuWarehouse = document.querySelector(
  "a.nav-item i.fa-warehouse",
)?.parentElement;

/*const menuTrucks = document.querySelector(
  "a.nav-item i.fa-truck",
)?.parentElement;*/

const menuPricing = document.querySelector(
  "a.nav-item i.fa-pricing",
)?.parentElement;

const allNavItems = document.querySelectorAll(".sidebar .nav-item");

function setActiveMenu(activeItem) {
  allNavItems.forEach((item) => item.classList.remove("active"));
  activeItem.classList.add("active");
}

if (menuDashboard) {
  menuDashboard.addEventListener("click", function (e) {
    e.preventDefault();
    setActiveMenu(this);
    document.title = "MD Logistic - Dashboard Vận hành";

    fetch("../../../dashboard/Dashboard.php")
      .then((res) => res.text())
      .then((html) => {
        const mainView = document.getElementById("main-view");
        mainView.innerHTML = html;
        mainView
          .querySelectorAll("script")
          .forEach((s) => window.eval(s.innerText));
      });
  });
}

if (menuOrders) {
  menuOrders.addEventListener("click", function (e) {
    e.preventDefault();
    setActiveMenu(this);
    document.title = "MD Logistic - Quản lý Đơn hàng";

    if (typeof window.loadOrderPage === "function") {
      window.loadOrderPage("?status=all&page=1");
    }
  });
}

if (menuCoordination) {
  menuCoordination.addEventListener("click", function (e) {
    e.preventDefault();
    setActiveMenu(this);
    document.title = "MD Logistic - Điều phối đơn hàng";

    const mainView = document.getElementById("main-view");

    // Nhúng toàn bộ trang Warehouse qua iframe
    mainView.innerHTML = `
      <iframe 
        src="../../orders/coordination/dispatch.php" 
        style="width: 100%; height: calc(100vh - 85px); border: none; display: block;"
      ></iframe>
    `;
  });
}

if (menuWarehouse) {
  menuWarehouse.addEventListener("click", function (e) {
    e.preventDefault();
    setActiveMenu(this);
    document.title = "MD Logistic - Quản trị Kho bãi";

    const mainView = document.getElementById("main-view");

    // Nhúng toàn bộ trang Warehouse qua iframe
    mainView.innerHTML = `
      <iframe 
        src="../../warehouse/index.php" 
        style="width: 100%; height: calc(100vh - 85px); border: none; display: block;"
      ></iframe>
    `;
  });
}

/*if (menuTrucks) {
  menuTrucks.addEventListener("click", function (e) {
    e.preventDefault();
    setActiveMenu(this);
    document.title = "MD Logistic - Quản trị Xe tải";

    const mainView = document.getElementById("main-view");

    // Nhúng toàn bộ trang Trucks qua iframe
    mainView.innerHTML = `
      <iframe 
        src="../../trucks/index.php" 
        style="width: 100%; height: calc(100vh - 85px); border: none; display: block;"
      ></iframe>
    `;
  });
}*/

if (menuPricing) {
  menuPricing.addEventListener("click", function (e) {
    e.preventDefault();
    setActiveMenu(this);
    document.title = "MD Logistic - Bảng giá cước";

    const mainView = document.getElementById("main-view");

    // Nhúng toàn bộ trang Warehouse qua iframe
    mainView.innerHTML = `
      <iframe 
        src="../../pricing/index.php" 
        style="width: 100%; height: calc(100vh - 85px); border: none; display: block;"
      ></iframe>
    `;
  });
}

const activeMenu = document.querySelector(".sidebar .nav-item.active");
if (activeMenu) {
  activeMenu.click();
} else if (menuOrders) {
  menuOrders.click();
}

// --- XỬ LÝ MENU DROPDOWN XE TẢI ---
const menuTrucksMain = document.getElementById("menu-trucks-main");
const submenuTrucks = document.getElementById("submenu-trucks");
const subNavItems = document.querySelectorAll(".sub-nav-item");

if (menuTrucksMain) {
  menuTrucksMain.addEventListener("click", function (e) {
    e.preventDefault();
    const icon = this.querySelector(".dropdown-icon");

    // Toggle hiển thị menu con
    if (submenuTrucks.style.display === "none") {
      submenuTrucks.style.display = "block";
      icon.classList.remove("fa-chevron-down");
      icon.classList.add("fa-chevron-up");
    } else {
      submenuTrucks.style.display = "none";
      icon.classList.remove("fa-chevron-up");
      icon.classList.add("fa-chevron-down");
    }
  });
}

// Xử lý khi bấm vào từng mục con (Xe tải / Tài xế / Vận hành)
if (subNavItems.length > 0) {
  subNavItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      e.preventDefault();

      // Xóa class active của TẤT CẢ các thẻ nav
      document
        .querySelectorAll(".nav-item")
        .forEach((n) => n.classList.remove("active"));
      document
        .querySelectorAll(".sub-nav-item")
        .forEach((n) => n.classList.remove("active"));

      // Kích hoạt màu cho menu cha và menu con vừa click
      menuTrucksMain.classList.add("active");
      this.classList.add("active");

      // Đổi tiêu đề tab
      const titleName = this.innerText;
      document.title = "MD Logistic - " + titleName;

      // Nhúng iframe trang tương ứng
      const srcUrl = this.getAttribute("data-src");
      const mainView = document.getElementById("main-view");

      mainView.innerHTML = `
        <iframe 
          src="${srcUrl}" 
          style="width: 100%; height: calc(100vh - 85px); border: none; display: block;"
        ></iframe>
      `;
    });
  });
}
