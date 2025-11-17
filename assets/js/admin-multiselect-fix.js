/*! Admin Multiselect Fix
 * Requires:
 *  - jQuery >= 3.6
 *  - Bootstrap 5 (bundle)
 *  - bootstrap-multiselect 0.9.15
 *
 * What this file does:
 *  - Initializes #roles and #permissions with bootstrap-multiselect using BS5-compatible templates.
 *  - Avoids double-initialization and common RTL/overflow/pointer-events issues.
 */
(function (window, $) {
  'use strict';

  if (!window || !window.document) return;

  // Prevent double run
  if (window.__breemMultiselectInit) return;
  window.__breemMultiselectInit = true;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function bs5TemplateButton() {
    return '' +
      '<button type="button" class="multiselect dropdown-toggle w-100 text-start" data-bs-toggle="dropdown">' +
      '<span class="multiselect-selected-text"></span> <b class="caret"></b>' +
      '</button>';
  }

  function initMultiselect($el, nonSelectedText) {
    if (!$el.length) return;
    if (typeof $.fn.multiselect !== 'function') {
      console.error('bootstrap-multiselect not loaded');
      return;
    }
    // If already initialized, destroy first for cleanliness
    if ($el.data('multiselect')) {
      try { $el.multiselect('destroy'); } catch (e) {}
    }

    $el.multiselect({
      includeSelectAllOption: true,
      selectAllText: 'تحديد الكل',
      allSelectedText: 'تم تحديد الكل',
      nonSelectedText: nonSelectedText || 'اختر',
      buttonWidth: '100%',
      buttonClass: 'btn btn-light w-100 text-start',
      maxHeight: 220,
      numberDisplayed: 3,
      buttonContainer: '<div class="btn-group w-100" />',
      enableHTML: false,
      // Important for dropdown clipping inside cards/overflow
      container: 'body',
      templates: {
        button: bs5TemplateButton()
      },
      // Mirror RTL layout if page dir=rtl
      onInitialized: function($select, container) {
        var isRTL = document.documentElement.getAttribute('dir') === 'rtl';
        var dropdown = container.find('.multiselect-container.dropdown-menu');
        if (isRTL) {
          dropdown.addClass('dropdown-menu-end text-end');
          container.find('.multiselect-selected-text').attr('dir', 'rtl');
        }
      }
    });
  }

  ready(function () {
    initMultiselect($('#roles'), 'اختر الأدوار');
    initMultiselect($('#permissions'), 'اختر الصلاحيات');
  });

})(window, window.jQuery);
