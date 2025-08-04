<?php
// Generate a random 32-byte key and encode it in base64
$key = 'base64:' . base64_encode(random_bytes(32));
echo $key . "\n";

// Read the .env file
$envFile = file_get_contents('.env');

// Replace the empty APP_KEY with the generated key
$envFile = preg_replace('/APP_KEY=(.*)/', 'APP_KEY=' . $key, $envFile);

// Write the updated content back to the .env file
file_put_contents('.env', $envFile);

echo "Application key generated and updated in .env file\n";
