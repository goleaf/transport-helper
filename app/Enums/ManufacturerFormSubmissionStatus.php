<?php

namespace App\Enums;

enum ManufacturerFormSubmissionStatus: string
{
    case Ready = 'ready';
    case Submitted = 'submitted';
}
