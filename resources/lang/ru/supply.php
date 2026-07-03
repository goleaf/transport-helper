<?php

return [
    'navigation' => [
        'dashboard' => 'Панель',
        'calculations' => 'Расчеты',
        'order_proposals' => 'Предложения заказов',
        'supplier_orders' => 'Заказы поставщикам',
        'emails' => 'Письма',
        'ai_extractions' => 'AI извлечения',
        'form_autofill_runs' => 'Проверка автозаполнения',
        'supplier_confirmations' => 'Подтверждения поставщика',
        'carrier_quotes' => 'Предложения перевозчиков',
        'logistics' => 'Логистика',
        'notifications' => 'Уведомления',
        'pilot_uat' => 'Пилотный UAT',
        'integrations' => 'Интеграции',
        'health' => 'Проверка системы',
    ],
    'actions' => [
        'review' => 'Проверить',
        'continue' => 'Продолжить',
        'open' => 'Открыть',
        'back' => 'Назад',
    ],
    'statuses' => [
        'needs_review' => 'Требует проверки',
        'approved' => 'Утверждено',
        'sent' => 'Отправлено',
        'confirmed' => 'Подтверждено',
        'delayed' => 'Задержка',
        'completed' => 'Завершено',
        'pending_approval' => 'Ожидает утверждения',
    ],
    'warnings' => [
        'email_requires_approval' => 'Письмо должно быть утверждено перед отправкой.',
        'ai_not_final' => 'AI-подсказки не являются финальными значениями.',
        'extraction_not_apply' => 'Принятие извлечения не меняет бизнес-данные.',
        'carrier_not_automatic' => 'Рекомендация системы не выбирает перевозчика автоматически.',
        'safety_stock' => 'Страховой запас покрывает только T2-T3.',
    ],
    'dashboard' => [
        'title' => 'Панель снабжения',
        'action_queue' => 'Моя очередь действий',
        'environment' => 'Среда',
    ],
];
