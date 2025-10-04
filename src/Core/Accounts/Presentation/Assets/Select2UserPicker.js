(function($){
  window.tmtUserPicker = function($el, opts) {
    var settings = $.extend({
      action: 'tmt_crm_search_users',
      ajaxUrl: window.ajaxurl,
      nonce: '',
      must_cap: '',
      per_page: 20,
    }, opts || {});

    $el.select2({
      width: '100%',
      ajax: {
        delay: 250,
        transport: function (params, success, failure) {
          var page = params.data.page || 1;
          $.ajax({
            url: settings.ajaxUrl,
            data: {
              action: settings.action,
              _ajax_nonce: settings.nonce,
              q: params.data.term || '',
              page: page,
              per_page: settings.per_page,
              must_cap: settings.must_cap
            },
            dataType: 'json',
            success: function(res){
              if (!res || !res.success) { failure(res); return; }
              var items = res.data.items.map(function(it){
                return { id: it.id, text: it.label };
              });
              success({ results: items, pagination: { more: !!res.data.more } });
            },
            error: failure
          });
        }
      },
      placeholder: $el.data('placeholder') || 'Chọn người dùng...'
    });

    // preload label
    var initId = $el.data('initial-id');
    if (initId) {
      $.ajax({
        url: settings.ajaxUrl,
        data: {
          action: 'tmt_crm_user_label',
          _ajax_nonce: settings.nonce,
          id: initId
        },
        dataType: 'json'
      }).done(function(res){
        if (res && res.success && res.data && res.data.label) {
          var option = new Option(res.data.label, initId, true, true);
          $el.append(option).trigger('change');
        }
      });
    }
  };
})(jQuery);
