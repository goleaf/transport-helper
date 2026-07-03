<?php

namespace App\Enums;

enum CalculationScenarioStatus: string
{
    case Draft = 'draft';
    case Running = 'running';
    case Simulated = 'simulated';
    case SimulatedWithWarnings = 'simulated_with_warnings';
    case Failed = 'failed';
    case ConvertedToProposal = 'converted_to_proposal';
    case Archived = 'archived';
}
