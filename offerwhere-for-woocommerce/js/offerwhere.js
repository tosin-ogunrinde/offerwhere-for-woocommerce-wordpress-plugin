jQuery(document).ready(function ($) {
    'use strict';
    $("#offerwhere-form-ask-user-for-number-toggle-button").click(function () {
        $('#offerwhere-form-ask-user-for-number').toggle('fast');
    });

    if ($('.offerwhere-alert').length) {
        $('#offerwhere-form-ask-user-for-number').show();
    }
});
