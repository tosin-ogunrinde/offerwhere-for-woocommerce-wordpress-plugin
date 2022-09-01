<?php

if (!defined('ABSPATH')) {
    exit;
}

class Offerwhere_WooCommerce
{
    const OFFERWHERE_WOOCOMMERCE_CLASS = 'Offerwhere_WooCommerce';
    const OFFERWHERE_USER_NUMBER = 'offerwhere_num';
    const OFFERWHERE_USER_DISCOUNT_APPLIED = 'offerwhere_user_discount_applied';
    const OFFERWHERE_USER_ID = 'offerwhere_uid';

    public static function init()
    {
        add_action('wp_head', array(self::OFFERWHERE_WOOCOMMERCE_CLASS, 'offerwhere_enqueue_scripts'));
        add_action('woocommerce_payment_complete', array(self::OFFERWHERE_WOOCOMMERCE_CLASS,
            'offerwhere_woocommerce_payment_complete'), 10, 1);
        add_action('wp_logout', array(self::OFFERWHERE_WOOCOMMERCE_CLASS, 'offerwhere_wp_logout'));
        add_filter('woocommerce_cart_calculate_fees', array(self::OFFERWHERE_WOOCOMMERCE_CLASS,
            'offerwhere_woocommerce_cart_calculate_fees'), 10, 1);
        self::offerwhere_start_session();
    }

    private static function offerwhere_start_session()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public static function offerwhere_enqueue_scripts()
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
            return;
        }
        ?>
        <script id="offerwhere-points-bot"
                src="https://d27st42bbpp5a4.cloudfront.net/v1/offerwhere-points-bot.js?key=<?php echo esc_attr(Offerwhere_Settings::offerwhere_get_public_key()) ?>"
                defer crossOrigin="anonymous"></script>
        <?php
    }

    public static function offerwhere_woocommerce_cart_calculate_fees($cart)
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing() ||
            !array_key_exists(self::OFFERWHERE_USER_NUMBER, $_COOKIE) ||
            !Offerwhere_Validator::offerwhere_is_valid_user_number($_COOKIE[self::OFFERWHERE_USER_NUMBER])) {
            return;
        }
        $response = Offerwhere_API::offerwhere_get_user_transaction_snapshot(
            Offerwhere_Settings::offerwhere_get_organisation_id(),
            Offerwhere_Settings::offerwhere_get_loyalty_program_id(),
            $_COOKIE[self::OFFERWHERE_USER_NUMBER],
            Offerwhere_Settings::offerwhere_get_private_key()
        );
        if (is_array($response) && !is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === Offerwhere_HTTP_Status::OK) {
                $response_body = wp_remote_retrieve_body($response);
                $result = json_decode($response_body, true);
                if ($result['user']['dummy']) {
                    return;
                } elseif (!$result['user']['enabled']) {
                    return;
                } else {
                    $user_points = $result['points'];
                }
            } else {
                return;
            }
        } else {
            return;
        }
        if ($user_points < Offerwhere_Settings::offerwhere_get_default_points_per_redemption()) {
            unset($_SESSION[self::OFFERWHERE_USER_DISCOUNT_APPLIED]);
        }
        $reward = intdiv($user_points, Offerwhere_Settings::offerwhere_get_default_points_per_redemption()) *
            (Offerwhere_Settings::offerwhere_get_default_amount_per_redemption() / 100);
        if (empty($cart->recurring_cart_key) && $reward > 0 && $reward <= $cart->cart_contents_total) {
            $_SESSION[self::OFFERWHERE_USER_DISCOUNT_APPLIED] = $reward;
            $cart->add_fee(Offerwhere_Settings::offerwhere_get_loyalty_program_name(), -$reward);
        } else {
            unset($_SESSION[self::OFFERWHERE_USER_DISCOUNT_APPLIED]);
        }
    }

    public static function offerwhere_woocommerce_payment_complete($order_id)
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing() ||
            !array_key_exists(self::OFFERWHERE_USER_ID, $_COOKIE) ||
            !Offerwhere_Validator::offerwhere_is_valid_uuid($_COOKIE[self::OFFERWHERE_USER_ID])) {
            return;
        }
        $order = wc_get_order($order_id);
        if ($order->get_status() !== 'processing') {
            return;
        }
        $reward = array_key_exists(self::OFFERWHERE_USER_DISCOUNT_APPLIED, $_SESSION) ?
            $_SESSION[self::OFFERWHERE_USER_DISCOUNT_APPLIED] : null;
        $transaction = array(
            'userId' => $_COOKIE[self::OFFERWHERE_USER_ID],
            'activityId' => Offerwhere_Settings::offerwhere_get_activity_id(),
            'spend' => $order->get_total() * 100,
            'credit' => $reward ?
                ((Offerwhere_Settings::offerwhere_get_default_points_per_redemption() * $reward * 100) /
                    Offerwhere_Settings::offerwhere_get_default_amount_per_redemption()) : null
        );
        self::offerwhere_woocommerce_post_transaction($transaction, $order_id);
        self::offerwhere_clear_session(array(self::OFFERWHERE_USER_DISCOUNT_APPLIED));
    }

    private static function offerwhere_woocommerce_post_transaction($transaction, $order_id)
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
            return;
        }
        Offerwhere_API::offerwhere_post_user_transaction(
            Offerwhere_Settings::offerwhere_get_organisation_id(),
            Offerwhere_Settings::offerwhere_get_loyalty_program_id(),
            $transaction,
            Offerwhere_Settings::offerwhere_get_private_key(),
            sprintf(
                '%s~%s~%s',
                Offerwhere_Settings::offerwhere_get_loyalty_program_id(),
                Offerwhere_Settings::offerwhere_get_activity_id(),
                $order_id
            )
        );
    }

    private static function offerwhere_clear_session($keys)
    {
        foreach ($keys as &$value) {
            if (array_key_exists($value, $_SESSION)) {
                unset($_SESSION[$value]);
            }
        }
    }

    public static function offerwhere_wp_logout()
    {
        self::offerwhere_clear_session(array(self::OFFERWHERE_USER_DISCOUNT_APPLIED));
    }
}
