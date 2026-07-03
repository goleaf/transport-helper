<?php

namespace App\Enums;

enum BudgetPeriodType: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
    case Custom = 'custom';
}
