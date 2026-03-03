{{-- Vite compiled styles (Bootstrap 5, AdminLTE 4, Font Awesome 6, app styles) --}}
@vite(['app/resources/sass/app.scss'])

{{-- Google Fonts --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

{{-- Page specific styles --}}
@stack('page-styles')

{{-- Page specific inline styles --}}
@stack('inline-styles')
