<?php

namespace App\Enums;

enum UnknownSkuResolutionStatus: string
{
    case Unresolved = 'unresolved';
    case Mapped = 'mapped';
    case ChangeRequested = 'change_requested';
    case Ignored = 'ignored';
    case Rejected = 'rejected';
}
