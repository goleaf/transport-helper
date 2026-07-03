<?php

namespace App\Enums;

enum AiPromptVersion: string
{
    case SupplierEmailParserV1 = 'supplier_email_parser_v1';
    case SupplierReplyDraftV1 = 'supplier_reply_draft_v1';
    case CarrierQuoteParserV1 = 'carrier_quote_parser_v1';
    case EmailFormAutofillV1 = 'email_form_autofill_v1';
}
