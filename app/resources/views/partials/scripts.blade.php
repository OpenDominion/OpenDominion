{{-- Vendor scripts --}}
<script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/plugins/jQuery/jquery-2.2.3.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/bootstrap/js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vendor/admin-lte/js/app.min.js') }}"></script>

{{-- App scripts --}}
<script type="text/javascript" src="{{ mix('assets/app/js/app.js') }}"></script>

{{-- Page specific scripts --}}
@stack('page-scripts')

{{-- Page specific inline scripts --}}
@stack('inline-scripts')
