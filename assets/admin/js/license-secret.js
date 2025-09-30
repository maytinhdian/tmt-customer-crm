jQuery(function ($) {
  $(".tmt-reveal-secret").on("click", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id = $btn.data("id");
    var field = $btn.data("field"); // 'secret_primary' | 'secret_secondary'

    $.ajax({
      url:
        typeof TMTCRM_LicenseSecret !== "undefined" &&
        TMTCRM_LicenseSecret.ajax_url
          ? TMTCRM_LicenseSecret.ajax_url
          : ajaxurl,
      method: "POST",
      dataType: "json",
      data: {
        action: "tmt_crm_license_reveal_secret",
        _ajax_nonce: TMTCRM_LicenseSecret.nonce,
        id: id,
        field: field,
      },
    })
      .done(function (resp) {
        if (!resp || !resp.success) {
          alert(
            resp && resp.data && resp.data.message
              ? resp.data.message
              : "Reveal failed"
          );
          return;
        }

        var $cell = $btn.closest("td");
        var $input = $cell
          .find("input[type=password], input[type=text]")
          .first();

        if ($input.length) {
          // Trường hợp ở form Edit
          $input
            .val(resp.data.secret)
            .attr("type", "text")
            .prop("readonly", true);
        } else {
          // Trường hợp ở List view
          $cell.find(".tmt-secret-text").text(resp.data.secret);
        }

        $btn.remove();
      })
      .fail(function (xhr) {
        alert(
          "AJAX " + xhr.status + " – " + (xhr.responseText || "Request failed")
        );
      });
  });
});

jQuery(function ($) {
  $(document).on("click", ".tmt-reveal-secret", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id = $btn.data("id");
    var field = $btn.data("field"); // 'secret_primary' | 'secret_secondary'

    $.post(
      typeof TMTCRM_LicenseSecret !== "undefined" &&
        TMTCRM_LicenseSecret.ajax_url
        ? TMTCRM_LicenseSecret.ajax_url
        : ajaxurl,
      {
        action: "tmt_crm_license_reveal_secret",
        _ajax_nonce: TMTCRM_LicenseSecret.nonce,
        id: id,
        field: field, // Controller sẽ map sang *_encrypted
      },
      function (resp) {
        if (!resp || !resp.success) {
          alert(
            resp && resp.data && resp.data.message
              ? resp.data.message
              : "Reveal failed"
          );
          return;
        }
        // List view: thay mask bằng full key
        if ($btn.hasClass("tmt-reveal-secret-list")) {
          $btn.closest("td").find(".tmt-secret-text").text(resp.data.secret);
        }
        $btn.remove();
      },
      "json"
    ).fail(function (xhr) {
      alert(
        "AJAX " + xhr.status + " – " + (xhr.responseText || "Request failed")
      );
    });
  });
});
