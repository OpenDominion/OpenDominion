{{-- Vite compiled JS (jQuery, Bootstrap 5, AdminLTE 4, app scripts) --}}
@vite(['app/resources/js/app.js'])

{{-- Page specific scripts --}}
@stack('page-scripts')

{{-- Page specific inline scripts --}}
@stack('inline-scripts')
