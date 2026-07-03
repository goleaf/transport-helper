<?php

namespace App\Enums;

enum CarrierQuoteSourceType: string
{
    case Manual = 'manual';
    case AiEmailExtraction = 'ai_email_extraction';
    case FormAutofillRun = 'form_autofill_run';
    case QuoteRequestReply = 'quote_request_reply';
}
