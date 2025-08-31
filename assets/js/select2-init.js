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
        console.log('AJAX resp:', data);
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
  const $customer = $("#contact_id");
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
})(jQuery);
