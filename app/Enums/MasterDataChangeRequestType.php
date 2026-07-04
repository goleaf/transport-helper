<?php

namespace App\Enums;

enum MasterDataChangeRequestType: string
{
    case CreateProduct = 'create_product';
    case UpdateProduct = 'update_product';
    case CreateSupplier = 'create_supplier';
    case UpdateSupplier = 'update_supplier';
    case CreateAlias = 'create_alias';
    case UpdateAlias = 'update_alias';
    case SupplierProductMapping = 'supplier_product_mapping';
    case LifecycleChange = 'lifecycle_change';
    case MergeRequest = 'merge_request';
    case Other = 'other';
}
