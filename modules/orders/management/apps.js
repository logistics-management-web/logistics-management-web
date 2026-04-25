const menuDashboard = document.querySelector(
  "a.nav-item i.fa-chart-pie",
)?.parentElement;
const menuOrders = document.querySelector(
  "a.nav-item i.fa-clipboard-list",
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

const activeMenu = document.querySelector(".sidebar .nav-item.active");
if (activeMenu) {
  activeMenu.click();
} else if (menuOrders) {
  menuOrders.click();
}
