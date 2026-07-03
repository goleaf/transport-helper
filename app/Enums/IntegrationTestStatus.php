<?php

namespace App\Enums;

enum IntegrationTestStatus: string
{
    case NotTested = 'not_tested';
    case Success = 'success';
    case Warning = 'warning';
    case Failed = 'failed';
}
