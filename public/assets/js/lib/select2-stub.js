// Lightweight stub for Select2 to avoid errors when CDN is blocked.
// Provides a no-op implementation so existing code calling $('.select2').select2() doesn't throw.
(function ($) {
  try {
    if ($ && $.fn && !$.fn.select2) {
      $.fn.select2 = function () { return this; };
    }
  } catch (e) {
    // Silently ignore if jQuery is not available.
  }
})(window.jQuery);