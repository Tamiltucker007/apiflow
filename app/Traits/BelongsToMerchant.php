<?php

namespace App\Traits;

use App\Models\Merchant;
use App\Scopes\MerchantScope;
use App\Services\MerchantContext;

trait BelongsToMerchant
{
    public static function bootBelongsToMerchant(): void
    {
        static::addGlobalScope(new MerchantScope);

        static::creating(function ($model) {
            if (! $model->merchant_id) {
                $merchantId = app(MerchantContext::class)->merchantId();

                if ($merchantId) {
                    $model->merchant_id = $merchantId;
                }
            }
        });
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
