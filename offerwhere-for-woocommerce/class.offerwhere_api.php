<?php

if (!defined('ABSPATH')) {
    exit;
}

class Offerwhere_API
{
    const OFFERWHERE_API_BASE_URL = 'https://api.offerhwere.com';

    public static function offerwhere_get_user_transaction_snapshot(
        $organisation_id,
        $loyalty_program_id,
        $user_number,
        $token
    ) {
        $api = esc_url(sprintf(
            '%s/v1/organisations/%s/loyalty-programs/%s/user-transaction-snapshots?user-number=%s',
            self::OFFERWHERE_API_BASE_URL,
            $organisation_id,
            $loyalty_program_id,
            $user_number
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
