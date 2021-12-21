<?php

if (!defined('ABSPATH')) {
    exit;
}

class Offerwhere_API
{
    const OFFERWHERE_API_BASE_URL = 'https://api.offerwhere.com';

    public static function offerwhere_post_user_number_confirmation_requests(
        $organisation_id,
        $loyalty_program_id,
        $user_number,
        $token
    ) {
        $api = esc_url(sprintf(
            '%s/v1/organisations/%s/loyalty-programs/%s/user-number-confirmation-requests',
            self::OFFERWHERE_API_BASE_URL,
            $organisation_id,
            $loyalty_program_id
        ));
        $args = self::offerwhere_get_default_request_args($token);
        $args['body'] = $user_number;
        $args['headers']['Content-Type'] = 'text/plain; charset=utf-8';
        return wp_remote_post($api, $args);
    }

    public static function offerwhere_get_user_transaction_snapshot(
        $organisation_id,
        $loyalty_program_id,
        $user_number,
        $activation_code,
        $token
    ) {
        $api = esc_url(sprintf(
            '%s/v1/organisations/%s/loyalty-programs/%s/user-transaction-snapshots?%s',
            self::OFFERWHERE_API_BASE_URL,
            $organisation_id,
            $loyalty_program_id,
            $user_number !== null ? 'user-number=' . $user_number : 'activation-code=' . $activation_code
        ));
        return wp_remote_get($api, self::offerwhere_get_default_request_args($token));
    }

    private static function offerwhere_get_default_request_args($token): array
    {
        return array(
            'headers' => array(
                'Authorization' => sprintf('Bearer %s', $token)
            ),
        );
    }

    public static function offerwhere_get_loyalty_program($organisation_id, $loyalty_program_id, $token)
    {
        $api = esc_url(sprintf(
            '%s/v1/organisations/%s/loyalty-programs/%s',
            self::OFFERWHERE_API_BASE_URL,
            $organisation_id,
            $loyalty_program_id
        ));
        return wp_remote_get($api, self::offerwhere_get_default_request_args($token));
    }

    public static function offerwhere_post_user_transaction(
        $organisation_id,
        $loyalty_program_id,
        $user_transaction,
        $token,
        $idempotencyKey
    ) {
        $api = esc_url(sprintf(
            '%s/v1/organisations/%s/loyalty-programs/%s/transactions',
            self::OFFERWHERE_API_BASE_URL,
            $organisation_id,
            $loyalty_program_id
        ));
        $args = self::offerwhere_get_default_request_args($token);
        $args['body'] = json_encode($user_transaction);
        $args['headers']['Content-Type'] = 'application/json; charset=utf-8';
        $args['headers']['X-Idempotency-Key'] = $idempotencyKey;
        return wp_remote_post($api, $args);
    }
}
