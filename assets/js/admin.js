(function () {
  const body = document.getElementById("tmt-quote-items");
  if (!body) return;

  function recalc() {
    let rows = body.querySelectorAll("tr");
    rows.forEach((tr) => {
      const qty = parseFloat(
        tr.querySelector('input[name="qty[]"]').value || 0
      );
      const price = parseFloat(
        tr.querySelector('input[name="unit_price[]"]').value || 0
      );
      const discount = parseFloat(
        tr.querySelector('input[name="discount[]"]').value || 0
      );
      const vat =
        parseFloat(tr.querySelector('input[name="tax_rate[]"]').value || 0) /
        100;
      const line = qty * price - discount;
      const total = Math.max(0, line) * (1 + vat);
      tr.querySelector(".tmt-line-total").textContent =
        total.toLocaleString("vi-VN");
    });
  }

  document.getElementById("tmt-row-add")?.addEventListener("click", () => {
    const tr = body.querySelector("tr").cloneNode(true);
    tr.querySelectorAll("input").forEach(
      (i) => (i.value = i.name === "qty[]" ? "1" : "0")
    );
    body.appendChild(tr);
    recalc();
  });

  body.addEventListener("click", (e) => {
    if (e.target.classList.contains("tmt-row-del")) {
      const rows = body.querySelectorAll("tr");
      if (rows.length > 1) {
        e.target.closest("tr").remove();
        recalc();
      }
    }
  });

  body.addEventListener("input", (e) => {
    if (e.target.matches("input")) recalc();
  });

  recalc();
})();
