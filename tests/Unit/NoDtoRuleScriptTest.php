<?php

test('no dto script enforces forbidden dto patterns', function () {
    $script = file_get_contents(dirname(__DIR__, 2).'/scripts/check-no-dto.sh');

    expect($script)
        ->toContain('app/Data')
        ->toContain('*DTO.php')
        ->toContain('*Dto.php')
        ->toContain('Spatie')
        ->toContain('LaravelData')
        ->toContain('DataTransferObject');
});
