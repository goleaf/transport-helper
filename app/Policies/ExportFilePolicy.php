<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ExportFile;
use App\Models\SupplierOrder;
use App\Models\User;

class ExportFilePolicy
{
    public function download(User $user, ExportFile $exportFile): bool
    {
        if ($exportFile->related_model_type === SupplierOrder::class && $exportFile->related_model_id !== null) {
            $order = SupplierOrder::query()
                ->select(['id', 'company_id', 'supplier_id', 'status'])
                ->find($exportFile->related_model_id);

            return $order instanceof SupplierOrder && $user->can('view', $order);
        }

        return $user->hasAnyRole([UserRole::Admin, UserRole::SupplyManager])
            || $user->hasPermission('view_calculations');
    }
}
