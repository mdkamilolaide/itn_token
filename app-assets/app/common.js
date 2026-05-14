//  Master of all data

//  Common namespace
window.common = {};
window.alert = {};
window.overlay = {};
//
//  Open function
//
String.prototype.StripTags = function () {
  return this.replace(/<[^>]+>/gi, "").replace("null", "");
};

function isEmpty(a) {
  return !a || 0 === a.length;
}

function getSettingValue(a, b) {
  var c = "";
  $.each(a, function () {
    if (this.EnumValue == b) {
      c = this.SettingValue;
      return;
    }
  });

  return c;
}

function getQueryStringParameter(c) {
  var b = window.location.search.substring(1);
  var e = b.split("&");
  for (var a = 0; a < e.length; a++) {
    var d = e[a].split("=");
    if (d[0] == c) {
      return d[1];
    }
  }
}

function GoBack() {
  window.history.back();
}

((overlay) => {
  const show = () => {
    $.blockUI({
      message: '<div class="spinner-border text-white" role="status"></div>',
      css: {
        backgroundColor: "transparent",
        border: "0",
      },
      overlayCSS: {
        opacity: 0.7,
      },
    });
  };

  const hide = () => {
    $.unblockUI();
  };
  //
  overlay.show = show;
  overlay.hide = hide;
})(window.overlay);

((common) => {
  //
  //  Common Properties
  //
  var BaseUrl = $("#v_g_prefix").val();
  var DataService = $("#v_g_prefix").val() + "services.data.php";
  var TableService = $("#v_g_prefix").val() + "services.table.php";
  var ExportService = $("#v_g_prefix").val() + "services.export.php";
  var BadgeService = $("#v_g_prefix").val() + "printbadge";
  var DpBadgeService = $("#v_g_prefix").val() + "printdpbadge";

  var MailService = "Mailing-Service-link";
  var ProcessService = "process-service-link";
  var ExportDownloadLimit = 25000;
  var ExportDownloadLimitLarge = 15000;
  //
  //  Common Methods
  //
  const GoToUrl = (a) => {
    location.href = a;
  };

  const GoToPage = (page) => {
    location.href = page;
  };

  const Pad = (str, max) => {
    str = str.toString();
    return str.length < max ? Pad("0" + str, max) : str;
  };
  /*
      Pad Usage
      ***********
      pad("3", 3);    // => "003"
      pad("123", 3);  // => "123"
      pad("1234", 3); // => "1234"

      var test = "MR 2";
      var parts = test.split(" ");
      parts[1] = pad(parts[1], 3);
      parts.join(" "); // => "MR 002"
    */
  //  Open up
  common.GoToPage = GoToPage;
  common.GoToUrl = GoToUrl;
  common.GoBack = GoBack;
  common.Pad = Pad;
  //  Properties
  common.DataService = DataService;
  common.ExportService = ExportService;
  common.MailService = MailService;
  common.BadgeService = BadgeService;
  common.DpBadgeService = DpBadgeService;
  common.ProcessService = ProcessService;
  common.TableService = TableService;
  common.ExportDownloadLimit = ExportDownloadLimit;
})(window.common);

((alert) => {
  const Success = (title, msg) => {
    toastr.success(msg, title, {
      positionClass: "toast-top-right",
      containerId: "toast-top-right",
      showMethod: "slideDown",
      hideMethod: "slideUp",
      progressBar: true,
    });
  };

  const Error = (title, msg) => {
    toastr.error(msg, title, {
      positionClass: "toast-top-right",
      containerId: "toast-top-right",
      showMethod: "slideDown",
      hideMethod: "slideUp",
      progressBar: true,
    });
  };

  const Info = (title, msg) => {
    toastr.info(msg, title, {
      positionClass: "toast-top-right",
      containerId: "toast-top-right",
      showMethod: "slideDown",
      hideMethod: "slideUp",
      progressBar: true,
    });
  };

  const Warning = (title, msg) => {
    toastr.warning(msg, title, {
      positionClass: "toast-top-right",
      containerId: "toast-top-right",
      showMethod: "slideDown",
      hideMethod: "slideUp",
      progressBar: true,
    });
  };

  const Delete = (page) => {
    $.confirm({
      theme: "light", //   supervan
      title: "Confirm Delete",
      content: "Are you sure you want to delete the selected item?",
      type: "red",
      icon: "fa fa-warning",
      //boxWidth: '500px',
      //useBootstrap: false,
      draggable: true,
      buttons: {
        Yes: {
          text: "Delete",
          btnClass: "btn-red",
          keys: ["enter", "shift"],
          action: () => {
            //  goto page
            GoToPage(page);
          },
        },
        Cancel: () => {
          //  Do nothing here
        },
      },
    });
  };
  //
  //
  //
  alert.Success = Success;
  alert.Error = Error;
  alert.Info = Info;
  alert.Warning = Warning;
  alert.Delete = Delete;
})(window.alert);

/**** Timer
 *
 * setInterval( function () {
    // whatever
    }, 30000 );
 */
$(window).ready(() => {
  setTimeout(() => {
    overlay.hide();
  }, 1000);
});
