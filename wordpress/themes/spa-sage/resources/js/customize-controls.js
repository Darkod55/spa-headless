/**
 * Site Identity: reveal "Show in footer" only while an alternative logo is set.
 * The PHP active_callback covers the saved value; this follows the live setting.
 */
(function (api) {
  api.bind("ready", function () {
    var logo = api("spa_alt_logo");
    var checkbox = api.control("spa_alt_logo_in_footer");
    if (!logo || !checkbox) {
      return;
    }

    var toggle = function (value) {
      var id = parseInt(value, 10);
      checkbox.active.set(!isNaN(id) && id > 0);
    };

    toggle(logo.get());
    logo.bind(toggle);
  });
})(wp.customize);
