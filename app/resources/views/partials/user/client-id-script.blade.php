<script type="text/javascript">
    (function ($) {
        try {
            var client = new ClientJS();
            var fingerprint = client.getFingerprint();
            $('#client_id').val(fingerprint);
        } catch {}
    })(jQuery);
</script>
