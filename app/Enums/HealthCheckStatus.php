<?php

namespace App\Enums;

enum HealthCheckStatus: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Error = 'error';
}
