{{-- Vite compiled styles (Bootstrap 5, AdminLTE 4, Font Awesome 6, app styles) --}}
@vite(['app/resources/sass/app.scss'])

{{-- Google Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600&display=swap">

{{-- Page specific styles --}}
@stack('page-styles')

{{-- Page specific inline styles --}}
@stack('inline-styles')
