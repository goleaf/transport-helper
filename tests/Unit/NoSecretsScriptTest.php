<?php

test('no secrets script checks secret-like files and patterns', function () {
    $script = file_get_contents(dirname(__DIR__, 2).'/scripts/check-no-secrets.sh');

    expect($script)
        ->toContain('.env')
        ->toContain('*.pem')
        ->toContain('OPENAI_API_KEY')
        ->toContain('GOOGLE_CLIENT_SECRET')
        ->toContain('AWS_SECRET_ACCESS_KEY')
        ->toContain('refresh_token')
        ->toContain('client_secret');
});
