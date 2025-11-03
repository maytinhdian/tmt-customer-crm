jQuery(function ($) {
  // Wrapper mới bạn đã đặt
  const $companyWrap = $("#tmt-company-select");
  const $customerWrap = $("#tmt-customer-select");

  // Select thật bên trong
  const $companySelect = $companyWrap.find("#company_id");
  const $customerSelect = $customerWrap.find("#customer_id");

  function toggleCustomerCompany() {
    const subject = $('input[name="subject"]:checked').val();

    if (subject === "company") {
      // Hiện Công ty, ẩn Khách lẻ
      $companyWrap.show();
      $companySelect.prop("disabled", false);

      $customerWrap.hide();
      $customerSelect.prop("disabled", true).val("").trigger("change.select2"); // reset nếu có select2
    } else {
      // Hiện Khách lẻ, ẩn Công ty
      $customerWrap.show();
      $customerSelect.prop("disabled", false);

      $companyWrap.hide();
      $companySelect.prop("disabled", true).val("").trigger("change.select2");
    }
  }

  // Lắng nghe đổi radio
  $(document).on("change", 'input[name="subject"]', toggleCustomerCompany);

  // Chạy ngay khi DOM sẵn sàng
  toggleCustomerCompany();
});
