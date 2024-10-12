<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('app.google_analytics_id') }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag("js", new Date());
  @if (Auth::user())
    gtag("config", "{{ config('app.google_analytics_id') }}", {
      "user_id": "{{ Auth::user()->id }}"
    });
  @else
    gtag("config", "{{ config('app.google_analytics_id') }}");
  @endif
</script>
