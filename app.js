const navItems = document.querySelectorAll(".nav-item");
const pages = document.querySelectorAll(".page");
const sidebar = document.getElementById("sidebar");
const openSidebarBtn = document.getElementById("openSidebar");
const collapseSidebarBtn = document.getElementById("collapseSidebar");
const backdrop = document.getElementById("backdrop");
const toastWrap = document.getElementById("toastWrap");

const partnerList = document.getElementById("partnerList");
const shareHint = document.getElementById("shareHint");
const partnerForm = document.getElementById("partnerForm");
const partnerName = document.getElementById("partnerName");
const partnerShare = document.getElementById("partnerShare");

const incomeForm = document.getElementById("incomeForm");
const incomeAmount = document.getElementById("incomeAmount");
const partnerSplit = document.getElementById("partnerSplit");
const companyOnly = document.getElementById("companyOnly");
const incomePreview = document.getElementById("incomePreview");

const expenseType = document.getElementById("expenseType");
const partnerSelectWrap = document.getElementById("partnerSelectWrap");

const chartSkeleton = document.getElementById("chartSkeleton");
const trendChartCanvas = document.getElementById("trendChart");

const partners = [
  { name: "Partner A", share: 30 },
  { name: "Partner B", share: 20 }
];

function showToast(message) {
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.textContent = message;
  toastWrap.appendChild(toast);
  setTimeout(() => toast.remove(), 2600);
}

function switchPage(targetId) {
  pages.forEach((page) => page.classList.toggle("active", page.id === targetId));
  navItems.forEach((item) => item.classList.toggle("active", item.dataset.target === targetId));
  sidebar.classList.remove("open");
  backdrop.classList.remove("open");
}

navItems.forEach((item) => {
  item.addEventListener("click", () => switchPage(item.dataset.target));
});

openSidebarBtn?.addEventListener("click", () => {
  sidebar.classList.add("open");
  backdrop.classList.add("open");
});

backdrop?.addEventListener("click", () => {
  sidebar.classList.remove("open");
  backdrop.classList.remove("open");
});

collapseSidebarBtn?.addEventListener("click", () => {
  sidebar.classList.toggle("collapsed");
});

function renderPartners() {
  partnerList.innerHTML = "";
  let total = 0;
  partners.forEach((p) => {
    total += p.share;
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${p.name}</td>
      <td>${p.share}%</td>
      <td>
        <button class="link-btn" type="button">Edit</button>
        <button class="link-btn danger" type="button">Delete</button>
      </td>
    `;
    partnerList.appendChild(tr);
  });

  shareHint.textContent = `Current total share: ${total}% ${total === 100 ? "(valid)" : "(must be 100%)"}`;
  shareHint.style.color = total === 100 ? "#166534" : "#b45309";
}

function updateIncomePreview() {
  const amount = Number(incomeAmount.value || 0);
  let companyPart = amount;
  let partnerPart = 0;

  if (companyOnly.checked) {
    companyPart = amount;
    partnerPart = 0;
    partnerSplit.checked = false;
  } else if (partnerSplit.checked) {
    partnerPart = amount * 0.5;
    companyPart = amount * 0.5;
  }

  incomePreview.textContent = `Company: $${companyPart.toFixed(2)} | Partners: $${partnerPart.toFixed(2)}`;
}

companyOnly.addEventListener("change", () => {
  if (companyOnly.checked) {
    partnerSplit.checked = false;
  }
  updateIncomePreview();
});

partnerSplit.addEventListener("change", () => {
  if (partnerSplit.checked) {
    companyOnly.checked = false;
  }
  updateIncomePreview();
});

incomeAmount.addEventListener("input", updateIncomePreview);
updateIncomePreview();

incomeForm.addEventListener("submit", (e) => {
  e.preventDefault();
  showToast("Income entry saved.");
  incomeForm.reset();
  partnerSplit.checked = true;
  companyOnly.checked = false;
  updateIncomePreview();
});

expenseType.addEventListener("change", () => {
  partnerSelectWrap.classList.toggle("hidden", expenseType.value !== "Partner Expense");
});

document.getElementById("expenseForm").addEventListener("submit", (e) => {
  e.preventDefault();
  showToast("Expense recorded.");
  e.target.reset();
  partnerSelectWrap.classList.add("hidden");
});

document.querySelectorAll("[data-open-modal]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const target = document.getElementById(btn.dataset.openModal);
    target?.classList.add("open");
    target?.setAttribute("aria-hidden", "false");
  });
});

document.querySelectorAll("[data-close-modal]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const target = document.getElementById(btn.dataset.closeModal);
    target?.classList.remove("open");
    target?.setAttribute("aria-hidden", "true");
  });
});

partnerForm.addEventListener("submit", (e) => {
  e.preventDefault();
  const name = partnerName.value.trim();
  const share = Number(partnerShare.value);
  if (!name || Number.isNaN(share) || share < 0 || share > 100) {
    showToast("Enter a valid name and share (0-100).");
    return;
  }
  partners.push({ name, share });
  renderPartners();
  e.target.reset();
  document.getElementById("partnerModal").classList.remove("open");
  showToast("Partner added.");
});

function initCharts() {
  setTimeout(() => {
    chartSkeleton.classList.add("hidden");
    trendChartCanvas.classList.remove("hidden");

    new Chart(trendChartCanvas, {
      type: "line",
      data: {
        labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        datasets: [
          {
            label: "Income",
            data: [4000, 5200, 4800, 7000, 6400, 7600, 8100],
            borderColor: "#22c55e",
            tension: 0.35
          },
          {
            label: "Expense",
            data: [1900, 2200, 2100, 2500, 2400, 2600, 3000],
            borderColor: "#ef4444",
            tension: 0.35
          }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById("reportChartA"), {
      type: "bar",
      data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May"],
        datasets: [
          { label: "Income", data: [14, 12, 15, 18, 16], backgroundColor: "#3b82f6" },
          { label: "Expense", data: [8, 6, 7, 10, 9], backgroundColor: "#ef4444" }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });

    new Chart(document.getElementById("reportChartB"), {
      type: "doughnut",
      data: {
        labels: ["Partner A", "Partner B", "Company Reserve"],
        datasets: [{ data: [30, 20, 50], backgroundColor: ["#3b82f6", "#22c55e", "#cbd5e1"] }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }, 900);
}

renderPartners();
initCharts();
