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

    // Owner (user)
    const $owner = $("#owner_id");
    if ($owner.length) {
      $owner.select2({
        width: "100%",
        placeholder: TMTCRM_Select2.i18n.placeholder_owner,
        language: {
          searching: function () {
            return TMTCRM_Select2.i18n.searching;
          },
          noResults: function () {
            return TMTCRM_Select2.i18n.no_results;
          },
        },
        ajax: buildAjax(TMTCRM_Select2.ajax_url, "tmt_crm_search_users"),
        minimumInputLength: 1,
      });
      ensureInitialValue($owner, "tmt_crm_get_user_label");
    }
  });
})(jQuery);
