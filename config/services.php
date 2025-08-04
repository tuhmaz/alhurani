<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | hCaptcha Configuration
    |--------------------------------------------------------------------------
    */
    'recaptcha' => [
        'site_key' => env('NOCAPTCHA_SITEKEY', function() {
            return cache()->remember('recaptcha_site_key', 3600, function() {
                return \App\Models\Setting::where('key', 'recaptcha_site_key')->value('value') ?? '';
            });
        }),
        'secret_key' => env('NOCAPTCHA_SECRET', function() {
            return cache()->remember('recaptcha_secret_key', 3600, function() {
                return \App\Models\Setting::where('key', 'recaptcha_secret_key')->value('value') ?? '';
            });
        }),
    ],


  /*
  |--------------------------------------------------------------------------
  | Third Party Services
  |--------------------------------------------------------------------------
  |
  | This file is for storing the credentials for third party services such
  | as Mailgun, Postmark, AWS and more. This file provides the de facto
  | location for this type of information, allowing packages to have
  | a conventional file to locate the various service credentials.
  |
  */

  'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
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

  'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],

'nocaptcha' => [
    'sitekey' => env('NOCAPTCHA_SITEKEY'),
    'secret' => env('NOCAPTCHA_SECRET'),
],

];
