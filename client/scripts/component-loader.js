/**
 * ============================================================
 *  SmartQ Component Loader  (jQuery)
 * ============================================================
 *
 *  HOW IT WORKS
 *  ─────────────────────────────────────────────────────────
 *  1. Add a placeholder <div> anywhere in your page:
 *
 *       <div data-component="sidebar"></div>
 *       <div data-component="topbar" data-props='{"title":"Dashboard"}'></div>
 *
 *  2. The loader maps each component name to a PHP file inside
 *     the `components/` folder (see COMPONENT_MAP below).
 *
 *  3. On DOM-ready, every [data-component] element is found and
 *     its matching PHP partial is fetched via jQuery `.load()`.
 *
 *  ADDING A NEW COMPONENT
 *  ─────────────────────────────────────────────────────────
 *  Just add one line to COMPONENT_MAP:
 *
 *      'my-widget': 'widgets/my-widget.php'
 *
 *  Then create the PHP file and drop a placeholder div on your page.
 *  That's it — zero boilerplate.
 * ============================================================
 */

(function ($) {
  "use strict";

  // Sidebar toggle for mobile
  $(document).on('click', '#sidebar-toggle', function() {
      $('.sidebar').toggleClass('open');
  });

  // Close sidebar when clicking outside on mobile
  $(document).on('click', function(e) {
      if ($(window).width() <= 768) {
          if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#sidebar-toggle').length) {
              $('.sidebar').removeClass('open');
          }
      }
  });

  // ── Base path from the page to the components folder ──────
  // Adjust this if your pages live at a different depth.
  // For pages at  client/pages/admin/  →  ../../components/
  const BASE_PATH = $('meta[name="component-base"]').attr("content") || "../../components/";

  // ── Component registry ────────────────────────────────────
  // Maps a short, human-friendly name → file path (relative to BASE_PATH).
  // This is the ONLY place you need to touch when adding a component.
  const COMPONENT_MAP = {
    /* ── Layout ─────────────────── */
    sidebar: "layout/sidebar.php",
    topbar: "layout/topbar.php",
    navbar: "layout/navbar.php",
    footer: "layout/footer.php",

    /* ── Widgets / Cards ────────── */
    "stat-card": "widgets/stat-card.php",
    "recent-activity": "widgets/recent-activity.php",
    graph: "widgets/graph.php",
    colleges: "widgets/colleges.php",
    "college-bar-chart": "widgets/college-bar-chart.php",
    "status-doughnut-chart": "widgets/status-doughnut-chart.php",

    // ➕ Add new components here…
  };

  // ── Lifecycle hooks (optional) ────────────────────────────
  // Register per-component callbacks if you need post-load logic.
  //
  //   SmartQ.onLoad('sidebar', function ($el) { … });
  //
  const _hooks = {};

  // ── Public API exposed as window.SmartQ ───────────────────
  window.SmartQ = window.SmartQ || {};

  /**
   * Register a callback that fires after a component is loaded.
   * @param {string}   name  Component name (matches data-component)
   * @param {Function} fn    Callback receiving the jQuery wrapper of the container
   */
  window.SmartQ.onLoad = function (name, fn) {
    if (!_hooks[name]) _hooks[name] = [];
    _hooks[name].push(fn);
  };

  /**
   * Manually load (or reload) a single component.
   * Useful for refreshing a widget without a full page reload.
   *
   *   SmartQ.load($('#stats-panel'));
   */
  window.SmartQ.load = function ($el) {
    _loadComponent($el);
  };

  /**
   * Re-scan the DOM and load any new [data-component] elements.
   * Handy after injecting HTML dynamically.
   *
   *   SmartQ.scan();
   */
  window.SmartQ.scan = function () {
    $("[data-component]").each(function () {
      const $el = $(this);
      if (!$el.data("sq-loaded")) {
        _loadComponent($el);
      }
    });
  };

  // ── Internal loader ───────────────────────────────────────
  function _loadComponent($el) {
    const name = $el.data("component");
    const file = COMPONENT_MAP[name];

    if (!file) {
      console.warn(`[SmartQ] Unknown component: "${name}". Check COMPONENT_MAP.`);
      return;
    }

    // Build the URL — append any data-props as query params so the
    // PHP partial can access them via $_GET.
    let url = BASE_PATH + file;
    const props = $el.data("props");
    if (props && typeof props === "object") {
      const qs = $.param(props);
      url += "?" + qs;
    }

    // Show a subtle loading state
    $el.addClass("sq-loading");

    // Fetch the component HTML
    $el.load(url, function (response, status, xhr) {
      $el.removeClass("sq-loading");

      if (status === "error") {
        console.error(
          `[SmartQ] Failed to load "${name}" from ${url}`,
          xhr.status,
          xhr.statusText
        );
        $el.html(
          `<div class="sq-error"> Component <strong>${name}</strong> could not be loaded.</div>`
        );
        return;
      }

      // Mark as loaded so SmartQ.scan() won't double-load
      $el.data("sq-loaded", true);

      // Fire any registered onLoad hooks
      if (_hooks[name]) {
        _hooks[name].forEach(function (fn) {
          fn($el);
        });
      }
    });
  }

  // ── Boot on DOM ready ─────────────────────────────────────
  $(function () {
    $("[data-component]").each(function () {
      _loadComponent($(this));
    });
  });

})(jQuery);
