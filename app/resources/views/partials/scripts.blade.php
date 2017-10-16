{{-- Vendor scripts --}}
<script type="text/javascript" src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/js/adminlte.min.js') }}"></script>

{{-- App scripts --}}
<script type="text/javascript" src="{{ mix('assets/app/js/app.js') }}"></script>

{{-- Page specific scripts --}}
@stack('page-scripts')

{{-- Page specific inline scripts --}}
@stack('inline-scripts')
