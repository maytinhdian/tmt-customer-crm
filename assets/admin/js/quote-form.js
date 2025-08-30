(function ($) {
  $(function () {
    var $el = $("#customer_id.tmt-select2-customer");
    if (!$el.length) return;

    $el.select2({
      width: "resolve",
      allowClear: true,
      placeholder: TMT_CRM_QUOTE.i18n.placeholder || "— Chọn —",
      ajax: {
        url: TMT_CRM_QUOTE.ajaxurl,
        dataType: "json",
        delay: 250,
        data: function (params) {
          return {
            action: "tmt_crm_customer_search",
            q: params.term || "",
            page: params.page || 1,
            nonce: TMT_CRM_QUOTE.nonce,
          };
        },
        processResults: function (data, params) {
          params.page = params.page || 1;
          return {
            results: data.results || [],
            pagination: { more: !!data.more },
          };
        },
        cache: true,
      },
      minimumInputLength: 1,
      templateResult: function (item) {
        if (item.loading) return item.text;
        // item: {id, text, company, contact}
        var $m = $("<div />");
        if (item.company) {
          $m.append($("<div />").text(item.company));
        } else {
          $m.append($("<div />").text(item.text));
        }
        if (item.contact) {
          $m.append($("<small />").text(item.contact));
        }
        return $m;
      },
      templateSelection: function (item) {
        return item.company || item.text || "";
      },
      escapeMarkup: function (m) {
        return m;
      },
    });
  });
})(jQuery);
