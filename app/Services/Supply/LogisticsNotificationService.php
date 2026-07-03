<?php

namespace App\Services\Supply;

use App\Enums\UserRole;
use App\Exceptions\NotConfiguredYetException;
use App\Models\User;
use App\Notifications\SupplyWorkflowNotification;
use Illuminate\Support\Collection;

class LogisticsNotificationService
{
    public const OrderPrepared = 'order_prepared';

    public const SupplierConfirmationReceived = 'supplier_confirmation_received';

    public const MissingReadyDate = 'missing_ready_date';

    public const DateDelay = 'date_delay';

    public const QuantityMismatch = 'quantity_mismatch';

    public const CarrierQuoteNeeded = 'carrier_quote_needed';

    public const CarrierSelected = 'carrier_selected';

    public const GoodsExpectedSoon = 'goods_expected_soon';

    public const GoodsArrived = 'goods_arrived';

    public const ImportFailed = 'import_failed';

    public const AiExtractionNeedsReview = 'ai_extraction_needs_review';

    public const FormAutofillNeedsReview = 'form_autofill_needs_review';

    public const FormAutofillApplied = 'form_autofill_applied';

    /**
     * @param  array<string, mixed>  $payload
     * @param  iterable<int, User>|null  $recipients
     * @return Collection<int, User>
     */
    public function notifyDatabase(string $eventType, array $payload = [], ?iterable $recipients = null): Collection
    {
        $users = $recipients === null ? $this->defaultRecipients() : collect($recipients);

        $users
            ->filter(fn (mixed $user): bool => $user instanceof User)
            ->unique('id')
            ->each(fn (User $user): mixed => $user->notify(new SupplyWorkflowNotification($eventType, $payload)));

        return $users->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyEmail(string $eventType, array $payload = []): never
    {
        throw new NotConfiguredYetException("Email notification channel for [{$eventType}] is not configured yet.");
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifySlackOrTeams(string $eventType, array $payload = []): never
    {
        throw new NotConfiguredYetException("Slack/Teams notification channel for [{$eventType}] is not configured yet.");
    }

    /**
     * @return Collection<int, User>
     */
    private function defaultRecipients(): Collection
    {
        return User::query()
            ->select(['id', 'name', 'email', 'password', 'role'])
            ->whereIn('role', [
                UserRole::Admin->value,
                UserRole::SupplyManager->value,
                UserRole::LogisticsManager->value,
            ])
            ->limit(100)
            ->get();
    }
}
