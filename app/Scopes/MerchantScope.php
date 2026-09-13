<?php

namespace App\Scopes;

use App\Services\MerchantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class MerchantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(MerchantContext::class);

        if ($context->merchantId() !== null) {
            $builder->where($model->getTable().'.merchant_id', $context->merchantId());
        }
    }
}
