(function ($) {
  function buildAjax(url, action) {
    return {
      url: url,
      dataType: "json",
      delay: 250,
      cache: true,
      data: function (params) {
        return {
          action: action,
          nonce: TMTCRM_Select2.nonce,
          term: params.term || "",
          page: params.page || 1,
        };
      },
      processResults: function (data, params) {
        params.page = params.page || 1;
        // Chuẩn Select2: { results: [], pagination: { more: bool } }
        var payload = data && data.data ? data.data : data;
        console.log("AJAX resp:", data);
        return payload; // ✅ luôn trả {results, pagination}
      },
    };
  }

  function ensureInitialValue($el, getAction) {
    const val = $el.data("initial-id");
    if (!val) return;
    $.getJSON(
      TMTCRM_Select2.ajax_url,
      {
        action: getAction,
        id: val,
        nonce: TMTCRM_Select2.nonce,
      },
      function (resp) {
        if (resp && resp.success && resp.data) {
          const opt = new Option(resp.data.text, resp.data.id, true, true);
          $el.append(opt).trigger("change");
        }
      }
    );
  }

  // Owner (user có quyền COMPANY_CREATE)
  const $owner = $("#owner_id");
  if ($owner.length) {
    let lastXhr = null;
    $owner.select2({
      width: "50%",
      placeholder: TMTCRM_Select2.i18n.placeholder_owner,
      language: {
        searching: function () {
          return TMTCRM_Select2.i18n.searching;
        },
        noResults: function () {
          return TMTCRM_Select2.i18n.no_results;
        },
      },
      ajax: Object.assign(
        buildAjax(TMTCRM_Select2.ajax_url, "tmt_crm_search_owners"),
        {
          transport: function (params, success, failure) {
            if (lastXhr && lastXhr.readyState !== 4) lastXhr.abort();
            lastXhr = $.ajax(params);
            lastXhr.then(success).fail(failure);
            return lastXhr;
          },
        }
      ),
      minimumInputLength: 2,
      templateSelection: function (item) {
        return item.text || item.id;
      },
    });
    ensureInitialValue($owner, "tmt_crm_get_owner_label");
  }

  //Customer (user có quyền COMPANY_CREATE)
  const $customer = $("select#customer_id");
  if ($customer.length) {
    $customer.select2({
      width: "100%",
      placeholder: $customer.data("placeholder") || "Chọn khách hàng...",
      language: {
        searching: function () {
          return TMTCRM_Select2.i18n.searching;
        },
        noResults: function () {
          return TMTCRM_Select2.i18n.no_results;
        },
      },
      ajax: buildAjax(
        TMTCRM_Select2.ajax_url,
        $customer.data("ajax-action") || "tmt_crm_search_customers"
      ),
      minimumInputLength: 1,
      templateResult: function (item) {
        return item.text;
      },
      templateSelection: function (item) {
        return item.text || item.id;
      },
    });
    // Đổ dữ liệu ban đầu khi edit form
    ensureInitialValue($customer, "tmt_crm_get_customer_label");
  }
  // Company (tìm kiếm công ty – dùng cho Báo giá, v.v.)
  const $company = $("select#company_id");
  if ($company.length) {
    $company.select2({
      width: "100%",
      placeholder: $company.data("placeholder") || "Chọn công ty...",
      language: {
        searching: function () {
          return TMTCRM_Select2.i18n.searching;
        },
        noResults: function () {
          return TMTCRM_Select2.i18n.no_results;
        },
      },
      ajax: buildAjax(
        TMTCRM_Select2.ajax_url,
        $company.data("ajax-action") || "tmt_crm_search_companies"
      ),
      minimumInputLength: 1,
      templateResult: function (item) {
        return item.text;
      },
      templateSelection: function (item) {
        return item.text || item.id;
      },
    });
    // Đổ dữ liệu ban đầu khi edit (nếu có data-initial-id)
    ensureInitialValue($company, "tmt_crm_get_company_label");
  }
  // Company & Contact (phụ thuộc vào company)
  // const $company = $("#company_id");
  const $contact = $("#contact_id");

  if ($company.length && $contact.length) {
    // Khởi tạo Select2 cho company
    $company.select2({
      width: "100%",
      placeholder: $company.data("placeholder") || "Chọn công ty...",
      ajax: buildAjax(TMTCRM_Select2.ajax_url, "tmt_crm_search_companies"),
      minimumInputLength: 1,
      templateResult: (item) => item.text,
      templateSelection: (item) => item.text || item.id,
    });
    ensureInitialValue($company, "tmt_crm_get_company_label");

    // Khởi tạo Select2 cho contact (cascading theo company_id)
    $contact.select2({
      width: "100%",
      placeholder: $contact.data("placeholder") || "Chọn liên hệ...",
      ajax: {
        url: TMTCRM_Select2.ajax_url,
        dataType: "json",
        delay: 250,
        cache: true,
        data: function (params) {
          return {
            action: "tmt_crm_search_contacts_by_company",
            company_id: $company.val(),
            term: params.term || "",
            page: params.page || 1,
            nonce: TMTCRM_Select2.nonce,
          };
        },
        processResults: function (data, params) {
          params.page = params.page || 1;
          var payload = data && data.data ? data.data : data;
          return payload;
        },
      },
      minimumInputLength: 0,
      templateResult: (item) => item.text,
      templateSelection: (item) => item.text || item.id,
    });

    // Khi đổi company → load liên hệ chính
    $company.on("change", function () {
      const companyId = $(this).val();
      if (!companyId) {
        $contact.val(null).trigger("change");
        return;
      }
      $.getJSON(
        TMTCRM_Select2.ajax_url,
        {
          action: "tmt_crm_get_primary_contact_by_company",
          company_id: companyId,
          nonce: TMTCRM_Select2.nonce,
        },
        function (resp) {
          if (resp && resp.success && resp.data) {
            const opt = new Option(resp.data.text, resp.data.id, true, true);
            $contact.empty().append(opt).trigger("change");
            // Có thể đổ thêm phone/email vào input riêng nếu cần
            $("#contact_phone").val(resp.data.phone || "");
            $("#contact_email").val(resp.data.email || "");
          } else {
            $contact.val(null).trigger("change");
          }
        }
      );
    });
  }
})(jQuery);
