<?php

return [
    'navigation' => [
        'dashboard' => 'Dashboard',
        'calculations' => 'Calculations',
        'order_proposals' => 'Order Proposals',
        'supplier_orders' => 'Supplier Orders',
        'emails' => 'Emails',
        'ai_extractions' => 'AI Extractions',
        'form_autofill_runs' => 'Form Autofill Runs',
        'supplier_confirmations' => 'Supplier Confirmations',
        'carrier_quotes' => 'Carrier Quotes',
        'logistics' => 'Logistics',
        'notifications' => 'Notifications',
        'pilot_uat' => 'Pilot UAT',
        'integrations' => 'Integrations',
        'health' => 'Health Check',
    ],
    'actions' => [
        'review' => 'Review',
        'continue' => 'Continue',
        'open' => 'Open',
        'back' => 'Back',
    ],
    'statuses' => [
        'needs_review' => 'Needs review',
        'approved' => 'Approved',
        'sent' => 'Sent',
        'confirmed' => 'Confirmed',
        'delayed' => 'Delayed',
        'completed' => 'Completed',
        'pending_approval' => 'Pending approval',
    ],
    'warnings' => [
        'email_requires_approval' => 'Email must be approved before sending.',
        'ai_not_final' => 'AI suggestions are not final values.',
        'extraction_not_apply' => 'Accepting extraction does not apply business changes.',
        'carrier_not_automatic' => 'System recommendation is not automatic carrier selection.',
        'safety_stock' => 'Safety stock covers only T2-T3.',
    ],
    'dashboard' => [
        'title' => 'Supply Dashboard',
        'action_queue' => 'My Action Queue',
        'environment' => 'Environment',
    ],
];
