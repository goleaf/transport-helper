<?php

namespace App\Enums;

enum ScenarioSimulationMode: string
{
    case Supplier = 'supplier';
    case ProductSet = 'product_set';
    case Category = 'category';
    case Company = 'company';
}
