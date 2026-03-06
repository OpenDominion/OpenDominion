{{-- Vendor scripts --}}
<script type="text/javascript" src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/ClientJS/0.2.1/client.base.min.js"></script>

{{-- AdminLTE scripts --}}
<script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/js/adminlte.min.js') }}"></script>

{{-- App scripts --}}
@vite(['app/resources/js/app.js'])

{{-- Page specific scripts --}}
@stack('page-scripts')

{{-- Page specific inline scripts --}}
@stack('inline-scripts')
