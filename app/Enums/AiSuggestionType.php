<?php

namespace App\Enums;

enum AiSuggestionType: string
{
    case EmailConfirmation = 'email_confirmation';
    case FormAutofill = 'form_autofill';
    case EmailReplyDraft = 'email_reply_draft';
}
