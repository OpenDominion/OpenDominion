{{-- Vendor styles --}}
<link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/font-awesome/css/font-awesome.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/rpg-awesome/css/rpg-awesome.css') }}">

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

{{-- Page specific styles --}}
@stack('page-styles')

{{-- Page specific inline styles --}}
@stack('inline-styles')

{{-- AdminLTe styles --}}
<link rel="stylesheet" href="{{ asset('assets/vendor/admin-lte/css/AdminLTE.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/admin-lte/css/skins/skin-blue.min.css') }}">

{{-- App styles --}}
<link rel="stylesheet" href="{{ mix('assets/app/css/app.css') }}">
