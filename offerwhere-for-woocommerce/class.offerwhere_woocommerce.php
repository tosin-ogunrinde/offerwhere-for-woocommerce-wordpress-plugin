<?php

if (!defined('ABSPATH')) {
    exit;
}

class Offerwhere_WooCommerce
{
    const OFFERWHERE_WOOCOMMERCE_CLASS = 'Offerwhere_WooCommerce';
    const OFFERWHERE_USER_NUMBER = 'offerwhere_user_number';
    const OFFERWHERE_ACTIVATION_CODE = 'offerwhere_activation_code';
    const OFFERWHERE_ACTIVATION_CODE_USER_NUMBER = 'offerwhere_activation_code_user_number';
    const OFFERWHERE_USER_POINTS = 'offerwhere_user_points';
    const OFFERWHERE_USER_DISCOUNT_APPLIED = 'offerwhere_user_discount_applied';
    const OFFERWHERE_USER_ID = 'offerwhere_user_id';
    const OFFERWHERE_USER_LOYALTY_POINTS_BALANCE = 'offerwhere_user_loyalty_points_balance';

    public static function init()
    {
        wp_enqueue_style(
            'offerwhere',
            plugin_dir_url(__FILE__) . 'css/offerwhere.css',
            array(),
            OFFERWHERE_VERSION
        );
        wp_enqueue_script(
            'offerwhere',
            plugin_dir_url(__FILE__) . 'js/offerwhere.js',
            array('jquery'),
            OFFERWHERE_VERSION
        );
        add_action('woocommerce_payment_complete', array(self::OFFERWHERE_WOOCOMMERCE_CLASS,
            'offerwhere_woocommerce_payment_complete'), 10, 1);
        add_action('woocommerce_before_checkout_form', array(self::OFFERWHERE_WOOCOMMERCE_CLASS,
            'offerwhere_woocommerce_before_checkout_form'));
        add_action('woocommerce_account_dashboard', array(self::OFFERWHERE_WOOCOMMERCE_CLASS,
            'offerwhere_woocommerce_account_dashboard'));
        add_action('woocommerce_thankyou', array(self::OFFERWHERE_WOOCOMMERCE_CLASS,
            'offerwhere_woocommerce_thankyou'), 10, 1);
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

    public static function offerwhere_woocommerce_before_checkout_form()
    {
        $order_total = 0;
        if (WC()->cart) {
            $order_total = WC()->cart->total;
        }
        self::offerwhere_show_card(true, true, false, $order_total);
    }

    private static function offerwhere_show_card(
        $show_points_to_collect,
        $show_edit_pin_form,
        $show_points_lost,
        $order_total
    ) {
        if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
            return;
        }
        ?>
        <div class="offerwhere">
            <div class="offerwhere-row">
                <div class="offerwhere-col">
                    <div class="offerwhere-card">
                        <div class="offerwhere-d-flex offerwhere-text-white">
                            <div class="offerwhere-w-100 offerwhere-pr-3">
                                <?php esc_html(printf(
                                    '<h3>%s</h3>',
                                    Offerwhere_Settings::offerwhere_get_loyalty_program_name()
                                )); ?>
                            </div>
                            <div class="offerwhere-border-left offerwhere-pl-3">
                                <a href="https://www.offerwhere.com" rel="noopener" target="_blank">
                                    <img class="offerwhere-logo" alt="Offerwhere logo" title="Offerwhere logo"
                                         src="data:image/png;base64,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">
                                </a>
                            </div>
                        </div>
                        <div class="offerwhere-body offerwhere-text-white offerwhere-pt-3">
                            <?php
                            if (Offerwhere_Settings::offerwhere_get_points_per_minimum_spend()) {
                                esc_html(printf(
                                    'Collect %s point%s for every %s you spend.
                Points add up to discounts. %s point%s gets you a %s discount.',
                                    Offerwhere_Settings::offerwhere_get_points_per_minimum_spend(),
                                    Offerwhere_Settings::offerwhere_get_points_per_minimum_spend() > 1 ? 's' : '',
                                    wc_price(Offerwhere_Settings::offerwhere_get_minimum_spend() / 100),
                                    Offerwhere_Settings::offerwhere_get_default_points_per_redemption(),
                                    Offerwhere_Settings::offerwhere_get_default_points_per_redemption() > 1 ? 's' : '',
                                    wc_price(Offerwhere_Settings::offerwhere_get_default_amount_per_redemption() / 100)
                                ));
                            } else {
                                esc_html(printf(
                                    'Collect %s point%s every time you spend at least %s.
                Points add up to discounts. %s point%s gets you a %s discount.',
                                    Offerwhere_Settings::offerwhere_get_points_per_transaction(),
                                    Offerwhere_Settings::offerwhere_get_points_per_transaction() > 1 ? 's' : '',
                                    wc_price(Offerwhere_Settings::offerwhere_get_minimum_spend() / 100),
                                    Offerwhere_Settings::offerwhere_get_default_points_per_redemption(),
                                    Offerwhere_Settings::offerwhere_get_default_points_per_redemption() > 1 ? 's' : '',
                                    wc_price(Offerwhere_Settings::offerwhere_get_default_amount_per_redemption() / 100)
                                ));
                            }
                            ?>
                            <div class="offerwhere-d-flex offerwhere-flex-row offerwhere-justify-content-center
                            offerwhere-pt-4
                            <?php esc_html(printf('%s', $show_edit_pin_form ? 'offerwhere-pb-4' : '')) ?>"
                                 id="offerwhere-icons-with-texts">
                                <?php
                                $user_number = null;
                                if (array_key_exists(self::OFFERWHERE_USER_NUMBER, $_SESSION)) {
                                    $user_number = $_SESSION[self::OFFERWHERE_USER_NUMBER];
                                }
                                ?>
                                <div class="offerwhere-d-flex offerwhere-flex-column offerwhere-align-items-center
                                <?php esc_html(printf('%s', $user_number === null ? 'offerwhere-d-none' : '')) ?>"
                                     id="offerwhere-user-number-icon-with-text">
                                    <div class="offerwhere-svg-container">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fal"
                                             data-icon="user-tag"
                                             class="svg-inline--fa fa-user-tag fa-w-20" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                            <path fill="currentColor"
                                                  d="M223.9 256c70.7 0 128-57.3 128-128S294.6 0 223.9 0C153.3 0 96 57.3 96 128s57.3 128 127.9 128zm0-224c52.9 0 96 43.1 96 96s-43.1 96-96 96-96-43.1-96-96c.1-52.9 43.1-96 96-96zm406.7 332.8l-90.2-90.3c-12-12-28.3-18.7-45.2-18.7h-79.3c-17.7 0-32 14.3-32 32v79.3c0 17 6.7 33.3 18.7 45.3l90.2 90.3c6.2 6.2 14.4 9.4 22.6 9.4 8.2 0 16.4-3.1 22.6-9.4l92.5-92.5c12.6-12.6 12.6-32.9.1-45.4zM515.5 480l-90.2-90.3c-6-6-9.4-14.1-9.4-22.6v-79.3h79.3c8.5 0 16.6 3.3 22.6 9.4l90.2 90.3-92.5 92.5zm-51.6-160c-8.8 0-16 7.2-16 16s7.2 16 16 16 16-7.2 16-16-7.2-16-16-16zm-64 160H48c-8.8 0-16-7.2-16-16v-41.6C32 365.9 77.9 320 134.4 320c19.6 0 39.1 16 89.6 16 50.3 0 70-16 89.6-16 13.6 0 26.5 2.8 38.4 7.6v-33.4c-12.2-3.7-25-6.2-38.4-6.2-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h351.9c15.6 0 29.3-7.6 38.1-19.1l-23.2-23.2c-2.4 6-8.1 10.3-14.9 10.3z"></path>
                                        </svg>
                                    </div>
                                    <div class="offerwhere-font-weight-bold offerwhere-text-uppercase
                                    offerwhere-text-small">
                                        PIN
                                    </div>
                                    <div class="offerwhere-text-truncate offerwhere-text-uppercase"
                                         id="offerwhere-user-number-text">
                                        <?php esc_html(printf('%s', $user_number !== null ? $user_number : '')) ?>
                                    </div>
                                </div>
                                <div class="offerwhere-d-flex offerwhere-flex-column offerwhere-align-items-center">
                                    <div class="offerwhere-svg-container">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="coins"
                                             class="svg-inline--fa fa-coins fa-w-16" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M336 32c-48.6 0-92.6 9-124.5 23.4-.9.4-51.5 21-51.5 56.6v16.7C76.1 132.2 0 163.4 0 208v192c0 44.2 78.8 80 176 80s176-35.8 176-80v-16.4c89.7-3.7 160-37.9 160-79.6V112c0-37-62.1-80-176-80zm-16 368c0 13.9-50.5 48-144 48S32 413.9 32 400v-50.1c31.8 20.6 84.4 34.1 144 34.1s112.2-13.5 144-34.1V400zm0-96c0 13.9-50.5 48-144 48S32 317.9 32 304v-50.1c31.8 20.6 84.4 34.1 144 34.1s112.2-13.5 144-34.1V304zm-144-48c-81 0-146.7-21.5-146.7-48S95 160 176 160s146.7 21.5 146.7 48S257 256 176 256zm304 48c0 13.1-45 43.6-128 47.3v-64.1c52.8-2.2 99.1-14.6 128-33.3V304zm0-96c0 13.1-45.1 43.4-128 47.2V208c0-5.6-1.7-11-4.1-16.3 54.6-1.7 102.4-14.5 132.1-33.8V208zm-144-48c-7.3 0-14-.5-20.9-.9-36.9-21.7-85-28.2-115.6-30-6.3-5.3-10.1-11-10.1-17.1 0-26.5 65.7-48 146.7-48s146.7 21.5 146.7 48S417 160 336 160z"/>
                                        </svg>
                                    </div>
                                    <div class="offerwhere-font-weight-bold offerwhere-text-uppercase
                                     offerwhere-text-small">
                                        Balance
                                    </div>
                                    <div id="offerwhere_loyalty_program_current_balance"
                                         class="offerwhere-text-truncate">
                                        <?php esc_html(printf(
                                            '%s',
                                            $user_number !== null &&
                                            array_key_exists(
                                                self::OFFERWHERE_USER_POINTS,
                                                $_SESSION
                                            ) ?
                                                $_SESSION[self::OFFERWHERE_USER_POINTS] : 0
                                        )) ?>
                                    </div>
                                </div>
                                <?php
                                if ($show_points_to_collect) {
                                    ?>
                                    <div class="offerwhere-d-flex offerwhere-flex-column offerwhere-align-items-center">
                                        <div class="offerwhere-svg-container">
                                            <svg aria-hidden="true" focusable="false" data-prefix="fal"
                                                 data-icon="hand-receiving"
                                                 class="svg-inline--fa fa-hand-receiving fa-w-20"
                                                 role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                                <path fill="currentColor"
                                                      d="M439.2 105.6L342.9 9.3C336.7 3.1 328.6 0 320.5 0s-16.2 3.1-22.4 9.3l-96.4 96.4c-12.3 12.3-12.3 32.4 0 44.7l96.4 96.4c6.2 6.2 14.3 9.3 22.4 9.3s16.2-3.1 22.4-9.3l96.4-96.4c12.3-12.4 12.3-32.4-.1-44.8zM320.5 224.4L224.1 128l96.3-96.4 96.4 96.4-96.3 96.4zM220 248.8c-14-19.2-49.1-31.4-74.5-3.9-15.6 16.8-15.9 42.8-2.5 61.3l28.6 39.3c6.5 8.9-6.5 19.1-13.6 10.7l-62-73.3V145.8c0-26-21.2-49.3-47.2-49.7C21.9 95.6 0 117.2 0 144v170.4c0 10.9 3.7 21.5 10.5 30l107 133.7c5.4 6.8 8.9 17.5 10.1 27 .5 4 4 6.9 8 6.9h16c4.8 0 8.5-3.9 8-8.7-1.6-16-7.5-33.3-17.1-45.2l-107-133.7c-2.3-2.8-3.5-6.4-3.5-10V144c0-21 32-21.6 32 .7v149.7l64.6 77.5c36.9 44.2 96.8-2.7 70.8-42.4-.2-.3-.4-.5-.5-.8l-30.6-42.2c-4.7-6.5-4.2-16.7 3.5-22.3 7-5.1 17.1-3.8 22.4 3.5l42.4 58.4c12.7 16.9 19.5 37.4 19.5 58v120c0 4.4 3.6 8 8 8h16c4.4 0 8-3.6 8-8v-120c0-27.7-9-54.6-25.6-76.8L220 248.8zM640 144c0-26.8-21.9-48.4-48.8-48-26 .4-47.2 23.7-47.2 49.7v137.1l-62 73.3c-7.1 8.4-20.1-1.7-13.6-10.7l28.6-39.3c13.4-18.5 13.1-44.6-2.5-61.3-25.5-27.4-60.6-15.3-74.5 3.9l-42.4 58.4C361 329.3 352 356.3 352 384v120c0 4.4 3.6 8 8 8h16c4.4 0 8-3.6 8-8V384c0-20.6 6.8-41.1 19.5-58l42.4-58.4c5.3-7.3 15.3-8.7 22.4-3.5 7.8 5.6 8.3 15.8 3.5 22.3l-30.6 42.2c-.2.3-.4.5-.5.8-26.1 39.7 33.9 86.7 70.8 42.4l64.6-77.5V144.6c0-22.3 32-21.7 32-.7v170.4c0 3.6-1.2 7.2-3.5 10L497.6 458c-9.5 11.9-15.5 29.2-17.1 45.2-.5 4.8 3.2 8.7 8 8.7h16c4 0 7.5-2.9 8-6.9 1.2-9.6 4.6-20.2 10.1-27l107-133.7c6.8-8.5 10.5-19.1 10.5-30L640 144z"/>
                                            </svg>
                                        </div>
                                        <div class="offerwhere-font-weight-bold offerwhere-text-uppercase
                                        offerwhere-text-small">
                                            Collect
                                        </div>
                                        <div class="offerwhere-text-truncate">
                                            <?php echo
                                            esc_attr(self::offerwhere_calculate_points_to_collect($order_total)) ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                if ($show_points_lost &&
                                    self::offerwhere_calculate_points_to_collect($order_total) > 0) {
                                    ?>
                                    <div class="offerwhere-d-flex offerwhere-flex-column offerwhere-align-items-center">
                                        <div class="offerwhere-svg-container">
                                            <svg aria-hidden="true" focusable="false" data-prefix="fal"
                                                 data-icon="heart-broken" class="svg-inline--fa fa-heart-broken fa-w-16"
                                                 role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                <path fill="currentColor"
                                                      d="M473.7 73.9c-39.6-41.2-83.8-41.7-95.5-41.7-28.7 0-57.5 9.4-81.3 28.2-5.2 4.1-7 11.4-4.9 17.6l25 75.4-85.8 57.2c-6.2 4.1-8.7 12-6.1 18.9l22.1 58.8-78-78 79.7-53.2c6-4 8.6-11.5 6.3-18.3l-17.4-52.5c-1.7-5-4.6-9.8-8.4-13.4-43.9-41.6-80.9-41.1-95-41.1-12 0-54.1 0-96.1 41.9C-10.4 123.7-12.5 203 31 256l212.1 218.5c3.5 3.6 8.2 5.5 12.8 5.5 4.7 0 9.3-1.8 12.8-5.5L481 256c43.5-53 41.4-132.3-7.3-182.1zM457 234.8l-201 207-201-207C22.3 194 25 133.4 61 96.6 81.1 76.4 103.8 64 134.4 64c27.3 0 51.6 10.3 72.8 32l13.7 41.5-85.8 57.2c-8.4 5.6-9.6 17.5-2.4 24.6l130.9 130.9c15.2 15.2 40.4-1 32.9-21.2l-37-98.8 85.4-57c6-4 8.6-11.5 6.3-18.4L326 79c15.6-9.7 33.6-14.8 52.2-14.8 26.7 0 51.3 9.9 72.6 32.1 36.3 37.1 38.9 97.7 6.2 138.5z"/>
                                            </svg>
                                        </div>
                                        <div class="offerwhere-font-weight-bold offerwhere-text-uppercase
                                        offerwhere-text-small">
                                            Lost
                                        </div>
                                        <div class="offerwhere-text-truncate">
                                            <?php echo
                                            esc_attr(self::offerwhere_calculate_points_to_collect($order_total)) ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                        if ($user_number === null) {
                            $user_id = get_current_user_id();
                            if ($user_id > 0) {
                                $user_number = Offerwhere_Database::offerwhere_get_user_number($user_id);
                                if ($user_number !== null) {
                                    self::offerwhere_get_user_transaction_snapshot($user_number, false);
                                }
                            }
                        }
                        if ($show_edit_pin_form) {
                            self::offerwhere_toggle_form_ask_for_user_number();
                            self::offerwhere_ask_user_for_number();
                            self::offerwhere_ask_user_for_activation_code();
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function offerwhere_calculate_points_to_collect($order_total): int
    {
        if ($order_total <= 0 || (($order_total * 100) < Offerwhere_Settings::offerwhere_get_minimum_spend())) {
            return 0;
        }
        if (Offerwhere_Settings::offerwhere_get_points_per_minimum_spend() !== null) {
            return intdiv($order_total, Offerwhere_Settings::offerwhere_get_points_per_minimum_spend());
        } else {
            return Offerwhere_Settings::offerwhere_get_points_per_transaction();
        }
    }

    private static function offerwhere_change_user_number($user_number)
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
            return;
        }
        if (!Offerwhere_Validator::offerwhere_is_valid_user_number($user_number)) {
            Offerwhere_Message::offerwhere_render_invalid_user_number_error_message();
            return;
        }
        if (array_key_exists(self::OFFERWHERE_USER_NUMBER, $_SESSION) &&
            strcasecmp($_SESSION[self::OFFERWHERE_USER_NUMBER], $user_number) === 0) {
            Offerwhere_Message::offerwhere_render_user_number_changed_successful_message();
            return;
        }
        $response = Offerwhere_API::offerwhere_post_user_number_confirmation_requests(
            Offerwhere_Settings::offerwhere_get_organisation_id(),
            Offerwhere_Settings::offerwhere_get_loyalty_program_id(),
            $user_number,
            Offerwhere_Settings::offerwhere_get_api_key()
        );
        if (is_array($response) && !is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === Offerwhere_HTTP_Status::NO_CONTENT) {
                $_SESSION[self::OFFERWHERE_ACTIVATION_CODE_USER_NUMBER] = $user_number;
            } else {
                self::offerwhere_clear_session(array(self::OFFERWHERE_ACTIVATION_CODE_USER_NUMBER));
                if ($response_code === Offerwhere_HTTP_Status::NOT_FOUND) {
                    Offerwhere_Message::offerwhere_render_unknown_user_number_error_message();
                } elseif ($response_code === Offerwhere_HTTP_Status::CONFLICT) {
                    Offerwhere_Message::offerwhere_render_user_disabled_error_message();
                } elseif ($response_code === Offerwhere_HTTP_Status::PRECONDITION_FAILED) {
                    Offerwhere_Message::offerwhere_render_unregistered_user_number_error_message();
                } else {
                    Offerwhere_Message::offerwhere_render_internal_server_error_message();
                }
            }
        }
    }

    private static function offerwhere_get_user_transaction_snapshot($user_number, $manual_entry)
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
            return;
        }
        if (!Offerwhere_Validator::offerwhere_is_valid_user_number($user_number)) {
            Offerwhere_Message::offerwhere_render_invalid_user_number_error_message();
            return;
        }
        $response = Offerwhere_API::offerwhere_get_user_transaction_snapshot(
            Offerwhere_Settings::offerwhere_get_organisation_id(),
            Offerwhere_Settings::offerwhere_get_loyalty_program_id(),
            $user_number,
            null,
            Offerwhere_Settings::offerwhere_get_api_key()
        );
        self::offerwhere_parse_get_user_transaction_snapshot_response($response, $user_number, $manual_entry, false);
    }

    private static function offerwhere_update_user_points($balance)
    {
        $current_balance = 0;
        if (isset($balance)) {
            $_SESSION[self::OFFERWHERE_USER_POINTS] = $balance;
            $current_balance = $balance;
        }
        $user_number = null;
        if (array_key_exists(self::OFFERWHERE_USER_NUMBER, $_SESSION)) {
            $user_number = $_SESSION[self::OFFERWHERE_USER_NUMBER];
        }
        ?>
        <script type="text/javascript">
            const balanceElement = document.getElementById('offerwhere_loyalty_program_current_balance');
            if (balanceElement !== null) {
                balanceElement.innerHTML = `<?php echo esc_attr($current_balance);?>`;
            }
            const userNumber = `<?php echo esc_attr($user_number);?>`;
            const userNumberIconWithTextElement = document.getElementById('offerwhere-user-number-icon-with-text');
            if (userNumber) {
                userNumberIconWithTextElement.classList.remove('offerwhere-d-none');
                document.getElementById('offerwhere-user-number-text').innerHTML = userNumber;
            } else {
                userNumberIconWithTextElement.classList.add('offerwhere-d-none');
            }
        </script>
        <?php
        do_action(self::OFFERWHERE_USER_LOYALTY_POINTS_BALANCE, $current_balance);
    }

    private static function offerwhere_toggle_form_ask_for_user_number()
    {
        ?>
        <div class="offerwhere-card-link">
            <a href="#" id="offerwhere-form-ask-user-for-number-toggle-button">
                Collect points? Apply Offerwhere PIN
            </a>
        </div>
        <?php
    }

    private static function offerwhere_ask_user_for_activation_code()
    {
        $button_submit_activation_code = 'offerwhere_btn_submit_activation_code';
        ?>
        <div class="offerwhere-form offerwhere-mt-3" id="offerwhere-form-activation-code-container"
             style=<?php echo array_key_exists(self::OFFERWHERE_ACTIVATION_CODE_USER_NUMBER, $_SESSION) ?
                'display:block' : 'display:none' ?>>
            <?php
            if (array_key_exists($button_submit_activation_code, $_POST) &&
                array_key_exists(self::OFFERWHERE_ACTIVATION_CODE, $_POST) &&
            array_key_exists(self::OFFERWHERE_ACTIVATION_CODE_USER_NUMBER, $_SESSION)) {
                self::offerwhere_apply_activation_code(
                    $_POST[self::OFFERWHERE_ACTIVATION_CODE],
                    $_SESSION[self::OFFERWHERE_ACTIVATION_CODE_USER_NUMBER]
                );
            }
            ?>
            <form method="post">
                <div class="offerwhere-pb-4">
                    We have sent an activation code to your email address. Enter this code below to change your PIN.
                </div>
                <?php
                woocommerce_form_field(self::OFFERWHERE_ACTIVATION_CODE, array(
                    'type' => 'text',
                    'required' => true,
                    'maxlength' => 6,
                    'autocomplete' => 'off',
                    'class' => array('offerwhere-form-row'),
                    'input_class' => array('offerwhere-text-uppercase', 'offerwhere-input-text'),
                    'label' => 'Activation code',
                ));
                ?>
                <input type="submit" class="button offerwhere-mt-3"
                       name="<?php echo esc_attr($button_submit_activation_code) ?>"
                       value="Apply activation code"/>
            </form>
        </div>
        <?php
    }

    private static function offerwhere_apply_activation_code($activation_code, $user_number)
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
            return;
        }
        if (!Offerwhere_Validator::offerwhere_is_valid_activation_code($activation_code)) {
            Offerwhere_Message::offerwhere_render_invalid_activation_code_error_message();
            return;
        }
        $response = Offerwhere_API::offerwhere_get_user_transaction_snapshot(
            Offerwhere_Settings::offerwhere_get_organisation_id(),
            Offerwhere_Settings::offerwhere_get_loyalty_program_id(),
            null,
            $activation_code,
            Offerwhere_Settings::offerwhere_get_api_key()
        );
        self::offerwhere_parse_get_user_transaction_snapshot_response(
            $response,
            $user_number,
            true,
            true
        );
    }

    private static function offerwhere_parse_get_user_transaction_snapshot_response(
        $response,
        $user_number,
        $manual_entry,
        $activation_journey
    ) {
        if (is_array($response) && !is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === Offerwhere_HTTP_Status::OK) {
                $response_body = wp_remote_retrieve_body($response);
                $result = json_decode($response_body, true);
                if ($result['user']['dummy']) {
                    Offerwhere_Message::offerwhere_render_unregistered_user_number_error_message();
                } elseif (!$result['user']['enabled']) {
                    Offerwhere_Message::offerwhere_render_user_disabled_error_message();
                } else {
                    $_SESSION[self::OFFERWHERE_USER_ID] = $result['user']['id'];
                    $_SESSION[self::OFFERWHERE_USER_NUMBER] = $user_number;
                    $current_user_id = get_current_user_id();
                    if ($current_user_id > 0) {
                        Offerwhere_Database::offerwhere_insert_user($current_user_id, $user_number);
                    }
                    if ($manual_entry && !$activation_journey) {
                        Offerwhere_Message::offerwhere_render_user_number_changed_successful_message();
                    }
                    self::offerwhere_update_user_points($result['points']);
                    self::offerwhere_clear_session(array(self::OFFERWHERE_ACTIVATION_CODE_USER_NUMBER));
                    ?>
                    <script type="application/javascript">
                        const activationCodeFormContainer = document
                            .getElementById('offerwhere-form-activation-code-container');
                        if (activationCodeFormContainer) {
                            activationCodeFormContainer.style.display = 'none';
                        }
                    </script>
                    <?php
                }
            } elseif ($response_code === Offerwhere_HTTP_Status::NOT_FOUND) {
                if ($activation_journey) {
                    Offerwhere_Message::offerwhere_render_unknown_activation_code_error_message();
                } else {
                    Offerwhere_Message::offerwhere_render_unknown_user_number_error_message();
                }
            } else {
                Offerwhere_Message::offerwhere_render_internal_server_error_message();
            }
        }
    }

    private static function offerwhere_ask_user_for_number()
    {
        $button_submit_user_number = 'offerwhere_btn_submit_user_number';
        ?>
        <div class="offerwhere-form offerwhere-mt-3" id="offerwhere-form-user-number-container" style="display: none">
            <?php
            if (array_key_exists($button_submit_user_number, $_POST) &&
                array_key_exists(self::OFFERWHERE_USER_NUMBER, $_POST)) {
                self::offerwhere_change_user_number($_POST[self::OFFERWHERE_USER_NUMBER]);
            }
            ?>
            <form method="post">
                <div class="offerwhere-pb-4">
                    An <a href="https://www.offerwhere.com" rel="noopener" target="_blank">Offerwhere PIN</a> is
                    required to collect the points. Enter your PIN below. If you don’t have
                    one, <a href="https://www.offerwhere.com/sign-up" rel="noopener"
                            target="_blank">sign up</a> to get yours now.
                </div>
                <?php
                woocommerce_form_field(self::OFFERWHERE_USER_NUMBER, array(
                    'type' => 'text',
                    'required' => true,
                    'maxlength' => 8,
                    'autocomplete' => 'off',
                    'class' => array('offerwhere-form-row'),
                    'input_class' => array('offerwhere-text-uppercase', 'offerwhere-input-text'),
                    'label' => 'Offerwhere PIN',
                ));
                ?>
                <input type="submit" class="button offerwhere-mt-3"
                       name="<?php echo esc_attr($button_submit_user_number) ?>"
                       value="Apply Offerwhere PIN"/>
            </form>
        </div>
        <?php
    }

    public static function offerwhere_woocommerce_cart_calculate_fees($cart)
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing() ||
            !array_key_exists(self::OFFERWHERE_USER_POINTS, $_SESSION)) {
            return;
        }
        $user_points = $_SESSION[self::OFFERWHERE_USER_POINTS];
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
        if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
            return;
        }
        $order = wc_get_order($order_id);
        if ($order->get_status() !== 'processing') {
            return;
        }
        $reward = array_key_exists(self::OFFERWHERE_USER_DISCOUNT_APPLIED, $_SESSION) ?
            $_SESSION[self::OFFERWHERE_USER_DISCOUNT_APPLIED] : null;
        $transaction = array(
            'userId' => $_SESSION[self::OFFERWHERE_USER_ID],
            'activityId' => Offerwhere_Settings::offerwhere_get_activity_id(),
            'spend' => $order->get_total() * 100,
            'credit' => $reward ?
                ((Offerwhere_Settings::offerwhere_get_default_points_per_redemption() * $reward * 100) /
                    Offerwhere_Settings::offerwhere_get_default_amount_per_redemption()) : null,
            'date' => date(DATE_ISO8601)
        );
        self::offerwhere_woocommerce_post_transaction($transaction, $order_id);
        self::offerwhere_clear_session(array(self::OFFERWHERE_USER_DISCOUNT_APPLIED));
    }

    private static function offerwhere_woocommerce_post_transaction($transaction, $order_id)
    {
        if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
            return;
        }
        $response = Offerwhere_API::offerwhere_post_user_transaction(
            Offerwhere_Settings::offerwhere_get_organisation_id(),
            Offerwhere_Settings::offerwhere_get_loyalty_program_id(),
            $transaction,
            Offerwhere_Settings::offerwhere_get_api_key(),
            sprintf(
                '%s~%s~%s',
                Offerwhere_Settings::offerwhere_get_loyalty_program_id(),
                Offerwhere_Settings::offerwhere_get_activity_id(),
                $order_id
            )
        );
        if (is_array($response) && !is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === Offerwhere_HTTP_Status::OK) {
                $response_body = wp_remote_retrieve_body($response);
                $result = json_decode($response_body, true);
                self::offerwhere_update_user_points($result['balance']);
                $user_id = get_current_user_id();
                if ($user_id > 0) {
                    Offerwhere_Database::offerwhere_insert_user($user_id, $_SESSION[self::OFFERWHERE_USER_NUMBER]);
                }
            }
        }
    }

    private static function offerwhere_clear_session($keys)
    {
        foreach ($keys as &$value) {
            if (array_key_exists($value, $_SESSION)) {
                unset($_SESSION[$value]);
            }
        }
    }

    public static function offerwhere_woocommerce_account_dashboard()
    {
        $order_total = 0;
        if (WC()->cart) {
            $order_total = WC()->cart->total;
        }
        self::offerwhere_show_card(false, true, false, $order_total);
    }

    public static function offerwhere_woocommerce_thankyou($order_id)
    {
        $order_total = 0;
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order_total = $order->get_total();
            }
        }
        self::offerwhere_show_card(
            false,
            false,
            !array_key_exists(self::OFFERWHERE_USER_NUMBER, $_SESSION),
            $order_total
        );
    }

    public static function offerwhere_wp_logout()
    {
        self::offerwhere_clear_session(array(self::OFFERWHERE_USER_DISCOUNT_APPLIED, self::OFFERWHERE_USER_ID,
            self::OFFERWHERE_USER_NUMBER, self::OFFERWHERE_USER_POINTS, self::OFFERWHERE_ACTIVATION_CODE_USER_NUMBER));
    }
}
