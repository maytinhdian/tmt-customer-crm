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
        return data;
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

  $(function () {
    // Company
    const $company = $("#company_id");
    if ($company.length) {
      $company.select2({
        width: "100%",
        placeholder: TMTCRM_Select2.i18n.placeholder_company,
        language: {
          searching: function () {
            return TMTCRM_Select2.i18n.searching;
          },
          noResults: function () {
            return TMTCRM_Select2.i18n.no_results;
          },
        },
        ajax: buildAjax(TMTCRM_Select2.ajax_url, "tmt_crm_search_companies"),
        minimumInputLength: 1,
        templateResult: function (item) {
          return item.text;
        },
        templateSelection: function (item) {
          return item.text || item.id;
        },
      });
      ensureInitialValue($company, "tmt_crm_get_company_label");
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

    // Customer (CompanyContactBox)
    const $customers = $(".js-customer-select, #contact_customer_id"); // tuỳ bạn dùng class hay id
    if ($customers.length) {
      $customers.each(function () {
        const $el = $(this);
        let lastXhr = null;

        $el.select2({
          width: "100%",
          placeholder:
            (TMTCRM_Select2.i18n && TMTCRM_Select2.i18n.placeholder_customer) ||
            "— Chọn khách hàng —",
          language: {
            searching: function () {
              return (
                (TMTCRM_Select2.i18n && TMTCRM_Select2.i18n.searching) ||
                "Đang tìm..."
              );
            },
            noResults: function () {
              return (
                (TMTCRM_Select2.i18n && TMTCRM_Select2.i18n.no_results) ||
                "Không có kết quả"
              );
            },
          },
          ajax: Object.assign(
            buildAjax(TMTCRM_Select2.ajax_url, "tmt_crm_search_customers"),
            {
              // Huỷ request cũ để mượt hơn khi gõ nhanh
              transport: function (params, success, failure) {
                if (lastXhr && lastXhr.readyState !== 4) lastXhr.abort();
                lastXhr = $.ajax(params);
                lastXhr.then(success).fail(failure);
                return lastXhr;
              },
            }
          ),
          minimumInputLength: 2, // (khuyên) 2 ký tự để giảm request
          templateResult: function (item) {
            return item.text;
          },
          templateSelection: function (item) {
            return item.text || item.id;
          },
        });

        // Preload nhãn khi đã có ID trong DB
        ensureInitialValue($el, "tmt_crm_get_customer_label");
      });
    }
  });
})(jQuery);
