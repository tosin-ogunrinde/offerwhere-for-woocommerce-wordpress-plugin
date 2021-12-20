jQuery(document).ready(function ($) {
    'use strict';
    $("#offerwhere-form-ask-user-for-number-toggle-button").click(function () {
        $('#offerwhere-form-user-number-container').toggle('fast', function () {
            if ($('#offerwhere-form-user-number-container').is(':visible')) {
                $('#offerwhere-form-activation-code-container').hide();
            }
        });
    });

    if ($('.offerwhere-alert').length && $('#offerwhere-form-activation-code-container').is(':hidden')) {
        $('#offerwhere-form-user-number-container').show();
    }
});
