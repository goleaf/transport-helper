<?php

namespace App\Contracts\Import;

interface ImportPersisterInterface
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     * @return array{model_type:class-string,model_id:int,model:object,metadata?:array<string,mixed>}
     */
    public function persist(array $row, array $context = []): array;
}
