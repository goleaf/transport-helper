<?php

namespace App\Enums;

enum SupplierConfirmationSourceType: string
{
    case Manual = 'manual';
    case AiEmailExtraction = 'ai_email_extraction';
    case FormAutofillRun = 'form_autofill_run';
}
