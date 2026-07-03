<?php

namespace App\Enums;

enum EmailProvider: string
{
    case Gmail = 'gmail';
    case MicrosoftGraph = 'microsoft_graph';
    case ImapSmtp = 'imap_smtp';
    case Manual = 'manual';
}
