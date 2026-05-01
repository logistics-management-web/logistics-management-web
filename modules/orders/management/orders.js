window.loadOrderPage = function (params = "") {
  fetch("Orders.php" + params)
    .then((res) => res.text())
    .then((html) => {
      const view = document.getElementById("main-view");
      if (view) {
        view.innerHTML = html;
        view
          .querySelectorAll("script")
          .forEach((s) => window.eval(s.innerText));
      }
    });
};

window.moChiTietDon = function (id) {
  fetch("OrderDetail.php?id=" + id)
    .then((res) => res.text())
    .then((html) => {
      const subContainer = document.getElementById("sub-container");
      if (subContainer) {
        subContainer.innerHTML = html;
        subContainer
          .querySelectorAll("script")
          .forEach((s) => window.eval(s.innerText));
      }
    })
    .catch((err) => console.error("Lỗi tải chi tiết:", err));
};

window.moFormTaoDon = function () {
  document.getElementById("create-order-overlay").classList.add("open");
  document.getElementById("create-order-box").classList.add("open");
};

window.dongFormTaoDon = function () {
  document.getElementById("create-order-overlay").classList.remove("open");
  document.getElementById("create-order-box").classList.remove("open");

  const box = document.getElementById("create-order-box");
  if (box) {
    box
      .querySelectorAll(
        "input[type='text']:not([disabled]), input[type='number']",
      )
      .forEach((el) => (el.value = ""));
    box.querySelectorAll("select").forEach((el) => (el.selectedIndex = 0));
    box.querySelectorAll("textarea").forEach((el) => (el.value = ""));
    let nameInput = document.getElementById("input-customer-name");
    if (nameInput) nameInput.style.borderColor = "#e2e8f0";
  }
};

document.addEventListener("focusout", function (e) {
  if (e.target && e.target.id === "input-phone-receive") {
    let phone = e.target.value.trim();
    if (phone.length >= 9) {
      let fd = new FormData();
      fd.append("action", "check_phone");
      fd.append("phone", phone);

      fetch("OrderAction.php", { method: "POST", body: fd })
        .then((res) => res.json())
        .then((data) => {
          if (data.status === "success") {
            let nameInput = document.getElementById("input-customer-name");
            nameInput.value = data.name;
            nameInput.style.borderColor = "#10b981";
          }
        })
        .catch((err) => {});
    }
  }
});

window.luuDonMoi = function () {
  let form_data = new FormData();
  form_data.append("action", "create");
  form_data.append("hub_id", document.getElementById("input-source").value);
  form_data.append(
    "phone",
    document.getElementById("input-phone-receive").value,
  );
  form_data.append(
    "name",
    document.getElementById("input-customer-name").value,
  );
  form_data.append("city", document.getElementById("input-city").value);
  form_data.append("ward", document.getElementById("input-ward").value);
  form_data.append(
    "address",
    document.getElementById("input-address-detail").value,
  );
  form_data.append("goods", document.getElementById("input-goods-type").value);
  form_data.append("cod", document.getElementById("input-cod").value || 0);
  form_data.append(
    "weight",
    document.getElementById("input-weight").value || 0,
  );
  form_data.append(
    "shipping_fee",
    document.getElementById("input-shipping-fee").value || 0,
  );

  if (
    !form_data.get("hub_id") ||
    !form_data.get("phone") ||
    !form_data.get("name") ||
    !form_data.get("city") ||
    !form_data.get("address")
  ) {
    alert("Vui lòng điền đầy đủ các thông tin bắt buộc (*)");
    return;
  }

  let btnSave = document.getElementById("btn-save-order");
  let oldText = btnSave.innerHTML;
  btnSave.innerHTML = "Đang xử lý...";
  btnSave.disabled = true;

  fetch("OrderAction.php", {
    method: "POST",
    body: form_data,
  })
    .then((res) => res.json())
    .then((data) => {
      btnSave.innerHTML = oldText;
      btnSave.disabled = false;

      if (data.status === "success") {
        dongFormTaoDon();
        alert("Tạo đơn hàng mới thành công.");
        window.loadOrderPage("?status=all&page=1");
      } else {
        alert("Lỗi tạo đơn: " + data.message);
      }
    })
    .catch((err) => {
      btnSave.innerHTML = oldText;
      btnSave.disabled = false;
      alert("Lỗi kết nối máy chủ. Vui lòng kiểm tra lại hệ thống mạng.");
    });
};
function tinhCuocTuDong() {
  let khoId = document.getElementById("input-source").value;
  let tinh = document.getElementById("input-city").value;
  let kg = document.getElementById("input-weight").value;

  if (khoId && tinh && kg > 0) {
    let fd = new FormData();
    fd.append("action", "calc_fee");
    fd.append("hub_id", khoId);
    fd.append("city", tinh);
    fd.append("weight", kg);

    fetch("OrderAction.php", { method: "POST", body: fd })
      .then((res) => res.json())
      .then((data) => {
        document.getElementById("input-shipping-fee").value = data.fee || 0;
      })
      .catch(() => {});
  } else {
    let inputCuoc = document.getElementById("input-shipping-fee");
    if (inputCuoc) inputCuoc.value = "";
  }
}

document.addEventListener("change", (e) => {
  if (["input-source", "input-city", "input-weight"].includes(e.target.id))
    tinhCuocTuDong();
});
document.addEventListener("input", (e) => {
  if (e.target.id === "input-weight") tinhCuocTuDong();
});
let searchTimeout;
const searchInput = document.getElementById("global-search-input");

if (searchInput) {
  searchInput.addEventListener("input", function () {
    clearTimeout(searchTimeout);

    const keyword = this.value.trim();

    searchTimeout = setTimeout(() => {
      window.loadOrderPage(
        "?status=all&page=1&search=" + encodeURIComponent(keyword),
      );
    }, 500);
  });
}
