<?php

if (!defined('ABSPATH')) {
    exit;
}

class Offerwhere_Message
{
    public static function offerwhere_render_user_disabled_error_message()
    {
        self::offerwhere_render_error_message('You need to activate your account to use this PIN.');
    }

    public static function offerwhere_render_unknown_user_number_error_message()
    {
        self::offerwhere_render_error_message('PIN cannot be found. <a 
class="offerwhere-alert-link" href="https://www.offerwhere.com/sign-up" rel="noopener" target="_blank">
            Sign up</a> to get yours now.');
    }

    public static function offerwhere_render_unregistered_user_number_error_message()
    {
        self::offerwhere_render_error_message('You need to sign up to use this PIN online. <a
                                class="offerwhere-alert-link" href="https://www.offerwhere.com/sign-up"
                                rel="noopener" target="_blank">
                            Sign up</a> with this PIN to retain the points you have collected.');
    }

    public static function offerwhere_render_inactive_user_number_error_message()
    {
        self::offerwhere_render_error_message('You need to activate your account to use this PIN. Click the link in the email we sent to you to activate your account.');
    }

    public static function offerwhere_render_unknown_activation_code_error_message()
    {
        self::offerwhere_render_error_message('The activation code cannot be found or has expired.');
    }

    public static function offerwhere_render_invalid_activation_code_error_message()
    {
        self::offerwhere_render_error_message('Enter a valid activation code.');
    }

    public static function offerwhere_render_invalid_user_number_error_message()
    {
        self::offerwhere_render_error_message('Enter a valid PIN.');
    }

    public static function offerwhere_render_internal_server_error_message()
    {
        self::offerwhere_render_error_message('An error occurred while processing your request. Try again.');
    }

    public static function offerwhere_render_user_number_changed_successful_message()
    {
        self::offerwhere_render_success_message('Your PIN has been applied.');
    }

    private static function offerwhere_render_error_message($message)
    {
        ?>
        <div class="offerwhere-alert offerwhere-alert-danger">
            <?php echo $message; ?>
        </div>
        <?php
    }

    private static function offerwhere_render_success_message($message)
    {
        ?>
        <div class="offerwhere-alert offerwhere-alert-success">
            <?php echo $message; ?>
        </div>
        <?php
    }
}
