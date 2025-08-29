(function () {
  function fmt(v, cur) {
    const n = Number(v || 0);
    const s = n.toLocaleString("vi-VN");
    return cur === "USD" ? "$" + s : s + " ₫";
  }
  function num(el) {
    const v = parseFloat(el?.value || 0);
    return isNaN(v) ? 0 : v;
  }

  const wrap = document.querySelector(".wrap.tmtcrm");
  if (!wrap) return;

  const curSel = document.getElementById("qCurrency");
  const body = document.getElementById("itemBody");
  const tpl = document.getElementById("tmt-row-template");

  function add_row(preset) {
    if (!tpl || !body) return;
    const tr = document.createElement("tbody");
    tr.innerHTML = tpl.innerHTML.trim();
    const row = tr.firstElementChild;
    if (preset) {
      row.querySelector(".sku").value = preset.sku || "";
      row.querySelector(".name").value = preset.name || "";
      row.querySelector(".qty").value = preset.qty || 1;
      row.querySelector(".price").value = preset.price || 0;
      row.querySelector(".discount").value = preset.discount || 0;
      row.querySelector(".vat").value = preset.vat ?? 10;
    }
    row.querySelector(".btn-remove").addEventListener("click", () => {
      row.remove();
      recalc();
    });
    ["qty", "price", "discount", "vat"].forEach((cls) => {
      row.querySelector("." + cls).addEventListener("input", recalc);
    });
    body.appendChild(row);
    recalc();
  }

  function recalc() {
    if (!body) return;
    let subtotal = 0,
      disc_total = 0,
      tax_total = 0;
    body.querySelectorAll("tr").forEach((r) => {
      const qty = num(r.querySelector(".qty"));
      const price = num(r.querySelector(".price"));
      const discount = num(r.querySelector(".discount"));
      const vat = num(r.querySelector(".vat")) / 100;
      const line_sub = qty * price;
      const line_tax = Math.max(0, line_sub - discount) * vat;
      const line_total = line_sub - discount + line_tax;
      r.querySelector(".line_total").textContent = fmt(
        line_total,
        curSel?.value || "VND"
      );
      subtotal += line_sub;
      disc_total += discount;
      tax_total += line_tax;
    });
    const cur = curSel?.value || "VND";
    document.getElementById("subtotal").textContent = fmt(subtotal, cur);
    document.getElementById("discount_total").textContent = fmt(
      disc_total,
      cur
    );
    document.getElementById("tax_total").textContent = fmt(tax_total, cur);
    document.getElementById("grand_total").textContent = fmt(
      subtotal - disc_total + tax_total,
      cur
    );
  }

  document
    .getElementById("btnAddRow")
    ?.addEventListener("click", () => add_row());
  curSel?.addEventListener("change", recalc);

  // seed 2 dòng giống Canvas khi vào form mới
  if (document.getElementById("itemsTable")) {
    if (body?.children.length === 1) {
      body.innerHTML = "";
      add_row({
        sku: "CAM-4MP",
        name: "Camera 4MP ColorVu",
        qty: 4,
        price: 1200000,
        discount: 0,
        vat: 10,
      });
      add_row({
        sku: "NVR-16CH",
        name: "Đầu ghi 16 kênh",
        qty: 1,
        price: 3500000,
        discount: 0,
        vat: 10,
      });
    } else {
      recalc();
    }
  }

  // Fake status actions (giữ đúng trải nghiệm Canvas)
  const pill = document.getElementById("quoteStatusPill");
  document.getElementById("btnSendQuote")?.addEventListener("click", () => {
    if (pill) {
      pill.textContent = "sent";
      pill.className = "pill sent";
    }
    alert("Đánh dấu: đã gửi báo giá cho khách (DEMO).");
  });
  document.getElementById("btnAcceptQuote")?.addEventListener("click", () => {
    if (pill) {
      pill.textContent = "accepted";
      pill.className = "pill accepted";
    }
    alert("Đánh dấu: khách đã chấp nhận (DEMO).");
  });
})();
