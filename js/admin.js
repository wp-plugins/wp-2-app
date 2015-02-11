(function($) {

    var wp2app_apiurl = $("#wp2app_apiurl").val();
    var wp2app_wpsiteurl = $("#wp2app_wpsiteurl").val();
    var wp2app_id = $("#wp2app_id").val();

    // Steps
    $('.step').on('click', function () {
        var step = $(this).attr('data-step');
        $('.step').removeClass('active');
        $(this).addClass('active');
        $('.wp2app_step').addClass('hidden');
        $('.wp2app_step[data-step=' + step + ']').removeClass('hidden');
        if (step == 2) {
            $('#wp2app_refresh_payment_status').trigger('click');
        } else if (step == 3) {
            $('#wp2app_refresh_status').trigger('click');
        }
    });

    // Payment status renewal
    $('#wp2app_refresh_payment_status').on('click', function (e) {
        var mythis = $(this);
        mythis.addClass('loading');
        $("#wp2app_payment_status").html('updating...');

        // Check payment status
        $.get(wp2app_apiurl + 'payment/check/' + wp2app_id + '?siteUrl=' + wp2app_wpsiteurl, {}, function (response) {
            if (typeof response.errors != 'undefined' && response.errors.length > 0) {
                var error_string = '';
                for (var i = 0; i < response.errors.length; i++) {
                    error_string += response.errors[i] + "\r\n";
                }
                alert(error_string);
            } else if (typeof response.data != 'undefined' && response.data != null && typeof response.data.statusText != 'undefined') {
                $("#wp2app_payment_status").html(response.data.statusText);
            }

            mythis.removeClass('loading');
        }, "json");

        e.preventDefault();
    });

    // Status renewal
    $('#wp2app_refresh_status').on('click', function (e) {
        var mythis = $(this);
        mythis.addClass('loading');
        $("#wp2app_status").html('updating...');

        // Check site status
        $.get(wp2app_apiurl + 'site/check/' + wp2app_id + '?siteUrl=' + wp2app_wpsiteurl, {}, function (response) {

            if (typeof response.errors != 'undefined' && response.errors.length > 0) {
                var error_string = '';
                for (var i = 0; i < response.errors.length; i++) {
                    error_string += response.errors[i] + "\r\n";
                }
                alert(error_string);
            } else if (typeof response.data != 'undefined' && response.data != null) {
                // Update status text
                if (typeof response.data.statusText != 'undefined') {
                    $("#wp2app_status").html(response.data.statusText);
                }
                // Update play store link
                if (typeof response.data.playStoreUrl != 'undefined') {
                    $("#wp2app_playstore_url").html(response.data.playStoreUrl);
                    $("#wp2app_playstore_url").attr('href', response.data.playStoreUrl);
                }
                // Update app store link
                if (typeof response.data.appStoreUrl != 'undefined') {
                    $("#wp2app_appstore_url").html(response.data.appStoreUrl);
                    $("#wp2app_appstore_url").attr('href', response.data.appStoreUrl);
                }

            }

            mythis.removeClass('loading');
        }, "json");

        e.preventDefault();
    });

})(jQuery);