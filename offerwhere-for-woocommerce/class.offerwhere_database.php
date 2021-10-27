<?php

if (!defined('ABSPATH')) {
    exit;
}

global $jal_db_version;
$jal_db_version = '1.0';

class Offerwhere_Database
{
    const OFFERWHERE_USERS_TABLE_NAME = 'offerwhere_users';

    private static function offerwhere_get_users_table_name($wpdb): string
    {
        return $wpdb->prefix . self::OFFERWHERE_USERS_TABLE_NAME;
    }

    public static function offerwhere_create_user_table_if_not_exists()
    {
        global $wpdb;
        global $jal_db_version;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = self::offerwhere_get_users_table_name($wpdb);

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                user_id BIGINT UNSIGNED NOT NULL,
                user_number TEXT NOT NULL,
                PRIMARY KEY (user_id)) $charset_collate;";
        dbDelta($sql);
        add_option('jal_db_version', $jal_db_version);
    }

    public static function offerwhere_insert_user($user_id, $user_number)
    {
        global $wpdb;
        $wpdb->replace(
            self::offerwhere_get_users_table_name($wpdb),
            array('user_id' => $user_id, 'user_number' => strtoupper($user_number))
        );
    }

    public static function offerwhere_get_user_number($user_id)
    {
        global $wpdb;
        $table_name = self::offerwhere_get_users_table_name($wpdb);
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ), OBJECT);
        if ($result === null) {
            return null;
        }
        if (Offerwhere_Validator::offerwhere_is_valid_user_number($result->user_number)) {
            return $result->user_number;
        }
        return null;
    }

    public static function offerwhere_drop_user_table()
    {
        global $wpdb;
        $table_name = self::offerwhere_get_users_table_name($wpdb);
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
