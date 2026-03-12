{{-- jQuery loaded as a classic synchronous script so window.$ is available to
     inline page scripts before the deferred Vite module bundle executes. --}}
<script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>

{{-- Vite compiled JS (Bootstrap 5, AdminLTE 4, app scripts) --}}
@vite(['app/resources/js/app.js'])

{{-- Page specific scripts --}}
@stack('page-scripts')

{{-- Page specific inline scripts --}}
@stack('inline-scripts')
