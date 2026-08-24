<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mailjet' => [
        'key' => env('MAILJET_API_KEY'),
        'secret' => env('MAILJET_API_SECRET'),
        'templates' => [
            'welcome' => env('MAILJET_TEMPLATE_WELCOME'),
            'procurement_created' => env('MAILJET_TEMPLATE_PROCUREMENT_CREATED'),
            'procurement_updated' => env('MAILJET_TEMPLATE_PROCUREMENT_UPDATED'),
            'shipment_created' => env('MAILJET_TEMPLATE_SHIPMENT_CREATED'),
            'shipment_updated' => env('MAILJET_TEMPLATE_SHIPMENT_UPDATED'),
            'quote_submitted' => env('MAILJET_TEMPLATE_QUOTE_SUBMITTED'),
            'quote_rate_updated' => env('MAILJET_TEMPLATE_QUOTE_RATE_UPDATED'),
            'contact_ack' => env('MAILJET_TEMPLATE_CONTACT_ACK'),
            'contact_admin' => env('MAILJET_TEMPLATE_CONTACT_ADMIN'),
            'newsletter' => env('MAILJET_TEMPLATE_NEWSLETTER'),
            'pickup_delivery' => env('MAILJET_TEMPLATE_PICKUP_DELIVERY'),
            'frozen_cargo' => env('MAILJET_TEMPLATE_FROZEN_CARGO'),
        ],
    ],

];
