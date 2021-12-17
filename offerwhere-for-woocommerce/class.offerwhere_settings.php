<?php

if (!defined('ABSPATH')) {
    exit;
}

class Offerwhere_Settings
{
    const OFFERWHERE_SETTINGS_CLASS = 'Offerwhere_Settings';
    const OFFERWHERE_API_KEY = 'api_key';
    const OFFERWHERE_API_KEY_FIELD = 'offerwhere_api_key';
    const OFFERWHERE_ORGANISATION_ID = 'organisation_id';
    const OFFERWHERE_LOYALTY_PROGRAM_ID = 'loyalty_program_id';
    const OFFERWHERE_LOYALTY_PROGRAM_ID_FIELD = 'offerwhere_loyalty_program_id';
    const OFFERWHERE_LOYALTY_PROGRAM_NAME = 'loyalty_program_name';
    const OFFERWHERE_MINIMUM_SPEND = 'minimum_spend';
    const OFFERWHERE_POINTS_PER_MINIMUM_SPEND = 'points_per_minimum_spend';
    const OFFERWHERE_POINTS_PER_TRANSACTION = 'points_per_transaction';
    const OFFERWHERE_ACTIVITY_ID = 'activity_id';
    const OFFERWHERE_ACTIVITY_ID_FIELD = 'offerwhere_activity_id';
    const OFFERWHERE_DEFAULT_POINTS_PER_REDEMPTION = 'default_points_per_redemption';
    const OFFERWHERE_DEFAULT_AMOUNT_PER_REDEMPTION = 'default_amount_per_redemption';
    const OFFERWHERE_SETTINGS = 'offerwhere_settings';
    const OFFERWHERE_SETTINGS_PAGE_SLUG = 'offerwhere-settings';

    public static function init()
    {
        add_action('admin_menu', array(self::OFFERWHERE_SETTINGS_CLASS, 'offerwhere_add_menu'));
        add_action('admin_init', array(self::OFFERWHERE_SETTINGS_CLASS, 'offerwhere_admin_init'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'offerwhere_add_plugin_page_settings_link');
    }

    public static function offerwhere_add_menu()
    {
        add_options_page(
            'Offerwhere Settings',
            'Offerwhere',
            'manage_options',
            self::OFFERWHERE_SETTINGS_PAGE_SLUG,
            array(self::OFFERWHERE_SETTINGS_CLASS, 'offerwhere_display_page')
        );
    }

    public static function offerwhere_display_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_attr($GLOBALS['title']) ?></h1>
            <?php
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
            ?>
            <form action="options.php" method="post">
                <?php settings_fields(self::OFFERWHERE_SETTINGS); ?>
                <?php do_settings_sections(self::OFFERWHERE_SETTINGS_PAGE_SLUG); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function offerwhere_admin_init()
    {
        $api_key_section = 'offerwhere_settings_api_key_section';

        register_setting(
            self::OFFERWHERE_SETTINGS,
            self::OFFERWHERE_SETTINGS,
            array(self::OFFERWHERE_SETTINGS_CLASS, 'offerwhere_settings_callback')
        );

        add_settings_section(
            $api_key_section,
            'Loyalty Program Settings',
            array(self::OFFERWHERE_SETTINGS_CLASS, 'offerwhere_api_key_section_callback'),
            self::OFFERWHERE_SETTINGS_PAGE_SLUG
        );

        add_settings_field(
            self::OFFERWHERE_API_KEY_FIELD,
            'API Key',
            array(self::OFFERWHERE_SETTINGS_CLASS, 'offerwhere_api_key_field_callback'),
            self::OFFERWHERE_SETTINGS_PAGE_SLUG,
            $api_key_section
        );

        add_settings_field(
            self::OFFERWHERE_LOYALTY_PROGRAM_ID_FIELD,
            'Loyalty Program Id',
            array(self::OFFERWHERE_SETTINGS_CLASS, 'offerwhere_loyalty_program_id_field_callback'),
            self::OFFERWHERE_SETTINGS_PAGE_SLUG,
            $api_key_section
        );

        add_settings_field(
            self::OFFERWHERE_ACTIVITY_ID_FIELD,
            'Activity Id',
            array(self::OFFERWHERE_SETTINGS_CLASS, 'offerwhere_activity_id_field_callback'),
            self::OFFERWHERE_SETTINGS_PAGE_SLUG,
            $api_key_section
        );
    }

    /**
     * @throws Exception
     */
    public static function offerwhere_settings_callback($input)
    {
        $valid = array();
        if (Offerwhere_Validator::offerwhere_is_valid_jwt($input[self::OFFERWHERE_API_KEY])) {
            try {
                $data = json_decode(base64_decode(explode('.', $input[self::OFFERWHERE_API_KEY])[1]));
                if (Offerwhere_Validator::offerwhere_is_valid_uuid($data->organisation_id)) {
                    $valid[self::OFFERWHERE_API_KEY] = $input[self::OFFERWHERE_API_KEY];
                    $valid[self::OFFERWHERE_ORGANISATION_ID] = $data->organisation_id;
                } else {
                    self::offerwhere_add_settings_error_message(
                        self::OFFERWHERE_API_KEY_FIELD,
                        'Enter a valid API Key'
                    );
                }
            } catch (Exception $e) {
                self::offerwhere_add_settings_error_message(
                    self::OFFERWHERE_API_KEY_FIELD,
                    'Enter a valid API Key'
                );
            }
        } else {
            self::offerwhere_add_settings_error_message(
                self::OFFERWHERE_API_KEY_FIELD,
                'Enter a valid API Key'
            );
        }
        if (Offerwhere_Validator::offerwhere_is_valid_uuid($input[self::OFFERWHERE_LOYALTY_PROGRAM_ID])) {
            $valid[self::OFFERWHERE_LOYALTY_PROGRAM_ID] = $input[self::OFFERWHERE_LOYALTY_PROGRAM_ID];
        } else {
            self::offerwhere_add_settings_error_message(
                self::OFFERWHERE_LOYALTY_PROGRAM_ID_FIELD,
                'Enter a valid Loyalty Program Id'
            );
        }
        if (Offerwhere_Validator::offerwhere_is_valid_uuid($input[self::OFFERWHERE_ACTIVITY_ID])) {
            $valid[self::OFFERWHERE_ACTIVITY_ID] = $input[self::OFFERWHERE_ACTIVITY_ID];
        } else {
            self::offerwhere_add_settings_error_message(
                self::OFFERWHERE_ACTIVITY_ID_FIELD,
                'Enter a valid Activity Id'
            );
        }
        if (!array_key_exists(self::OFFERWHERE_API_KEY, $valid) ||
            !array_key_exists(self::OFFERWHERE_ORGANISATION_ID, $valid) ||
            !array_key_exists(self::OFFERWHERE_LOYALTY_PROGRAM_ID, $valid) ||
            !array_key_exists(self::OFFERWHERE_ACTIVITY_ID, $valid)) {
            return $valid;
        }
        $response = Offerwhere_API::offerwhere_get_loyalty_program(
            $valid[self::OFFERWHERE_ORGANISATION_ID],
            $valid[self::OFFERWHERE_LOYALTY_PROGRAM_ID],
            $valid[self::OFFERWHERE_API_KEY]
        );
        if (is_array($response) && !is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                $response_body = wp_remote_retrieve_body($response);
                $result = json_decode($response_body, true);
                $valid[self::OFFERWHERE_LOYALTY_PROGRAM_NAME] = $result['name'];
                $valid[self::OFFERWHERE_DEFAULT_AMOUNT_PER_REDEMPTION] = $result['defaultAmountPerRedemption'];
                $valid[self::OFFERWHERE_DEFAULT_POINTS_PER_REDEMPTION] = $result['defaultPointsPerRedemption'];
                if (isset($result['activities'])) {
                    $activity_found = false;
                    foreach ($result['activities'] as &$activity) {
                        if ($activity['id'] === $valid[self::OFFERWHERE_ACTIVITY_ID]) {
                            $activity_found = true;
                            if (isset($activity['loyaltySpendBasedReward'])) {
                                $valid[self::OFFERWHERE_MINIMUM_SPEND] =
                                    $activity['loyaltySpendBasedReward']['minimumSpend'];
                                $valid[self::OFFERWHERE_POINTS_PER_MINIMUM_SPEND] =
                                    $activity['loyaltySpendBasedReward']['pointsPerMinimumSpend'];
                                break;
                            } elseif (isset($activity['loyaltyPointReward'])) {
                                $valid[self::OFFERWHERE_MINIMUM_SPEND] =
                                    $activity['loyaltyPointReward']['minimumSpend'];
                                $valid[self::OFFERWHERE_POINTS_PER_TRANSACTION] =
                                    $activity['loyaltyPointReward']['pointsPerTransaction'];
                                break;
                            } else {
                                throw new Exception('Unsupported activity');
                            }
                        }
                    }
                    if (!$activity_found) {
                        self::offerwhere_add_settings_error_message(
                            self::OFFERWHERE_ACTIVITY_ID_FIELD,
                            'Activity Id cannot be found'
                        );
                    }
                }
            } elseif ($response_code === 401) {
                self::offerwhere_add_settings_error_message(
                    self::OFFERWHERE_API_KEY_FIELD,
                    'Enter a valid API Key'
                );
            } elseif ($response_code === 402) {
                self::offerwhere_add_settings_error_message(
                    self::OFFERWHERE_API_KEY_FIELD,
                    'You need an active subscription to run loyalty programs'
                );
            } elseif ($response_code === 404) {
                self::offerwhere_add_settings_error_message(
                    self::OFFERWHERE_LOYALTY_PROGRAM_ID_FIELD,
                    'Loyalty Program Id cannot be found'
                );
            }
        }
        return $valid;
    }

    private static function offerwhere_add_settings_error_message($field_id, $error_message)
    {
        add_settings_error(self::OFFERWHERE_SETTINGS, $field_id, $error_message, 'error');
    }

    public static function offerwhere_get_activity_id()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_ACTIVITY_ID);
    }

    private static function offerwhere_get_settings_val($settings_key)
    {
        $option = get_option(self::OFFERWHERE_SETTINGS);
        return isset($option) && isset($option[$settings_key]) ? $option[$settings_key] : null;
    }

    public static function offerwhere_api_key_section_callback()
    {
        ?>
        <p>Enter the values below to activate your loyalty program on this site.</p>
        <?php
    }

    public static function offerwhere_api_key_field_callback()
    {
        self::offerwhere_field_text_area_callback(self::OFFERWHERE_API_KEY_FIELD, self::OFFERWHERE_API_KEY);
    }

    private static function offerwhere_field_text_area_callback($field_id, $settings_key)
    {
        $value = self::offerwhere_get_settings_val($settings_key);
        ?>
        <textarea id="<?php echo esc_attr($field_id); ?>" cols="80" rows="6"
                  name="<?php esc_html(printf('%s[%s]', self::OFFERWHERE_SETTINGS, $settings_key)); ?>"><?php esc_html(printf('%s', $value !== null ? $value : '')); ?></textarea>
        <?php
    }

    public static function offerwhere_loyalty_program_id_field_callback()
    {
        self::offerwhere_field_input_text_callback(
            self::OFFERWHERE_LOYALTY_PROGRAM_ID_FIELD,
            self::OFFERWHERE_LOYALTY_PROGRAM_ID
        );
    }

    private static function offerwhere_field_input_text_callback($field_id, $settings_key)
    {
        $value = self::offerwhere_get_settings_val($settings_key);
        ?>
        <input type="text" id="<?php echo esc_attr($field_id); ?>"
               name="<?php esc_html(printf('%s[%s]', self::OFFERWHERE_SETTINGS, $settings_key)); ?>"
               class="regular-text"
               value="<?php esc_html(printf('%s', $value !== null ? $value : '')); ?>"/>
        <?php
    }

    public static function offerwhere_activity_id_field_callback()
    {
        self::offerwhere_field_input_text_callback(self::OFFERWHERE_ACTIVITY_ID_FIELD, self::OFFERWHERE_ACTIVITY_ID);
    }

    public static function offerwhere_is_setting_missing(): bool
    {
        $calculation_base_point = self::offerwhere_get_points_per_minimum_spend() !== null ?
            self::offerwhere_get_points_per_minimum_spend() : self::offerwhere_get_points_per_transaction();
        return !Offerwhere_Validator::offerwhere_is_valid_jwt(self::offerwhere_get_api_key()) ||
            !Offerwhere_Validator::offerwhere_is_valid_uuid(self::offerwhere_get_organisation_id()) ||
            !Offerwhere_Validator::offerwhere_is_valid_uuid(self::offerwhere_get_loyalty_program_id()) ||
            !Offerwhere_Validator::offerwhere_is_valid_uuid(self::offerwhere_get_activity_id()) ||
            self::offerwhere_get_default_amount_per_redemption() === null ||
            self::offerwhere_get_default_points_per_redemption() === null ||
            self::offerwhere_get_loyalty_program_name() === null ||
            self::offerwhere_get_minimum_spend() === null ||
            $calculation_base_point === null;
    }

    public static function offerwhere_get_api_key()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_API_KEY);
    }

    public static function offerwhere_get_organisation_id()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_ORGANISATION_ID);
    }

    public static function offerwhere_get_loyalty_program_id()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_LOYALTY_PROGRAM_ID);
    }

    public static function offerwhere_get_default_amount_per_redemption()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_DEFAULT_AMOUNT_PER_REDEMPTION);
    }

    public static function offerwhere_get_default_points_per_redemption()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_DEFAULT_POINTS_PER_REDEMPTION);
    }

    public static function offerwhere_get_loyalty_program_name()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_LOYALTY_PROGRAM_NAME);
    }

    public static function offerwhere_get_minimum_spend()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_MINIMUM_SPEND);
    }

    public static function offerwhere_get_points_per_minimum_spend()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_POINTS_PER_MINIMUM_SPEND);
    }

    public static function offerwhere_get_points_per_transaction()
    {
        return self::offerwhere_get_settings_val(self::OFFERWHERE_POINTS_PER_TRANSACTION);
    }

    public static function offerwhere_delete_settings()
    {
        delete_option(self::OFFERWHERE_SETTINGS);
    }
}
