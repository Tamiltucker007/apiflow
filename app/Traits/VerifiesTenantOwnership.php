<?php

namespace App\Traits;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Model;

// Route-model-bound params (e.g. {plan}, {customer}) resolve before
// merchant.access middleware sets tenant scoping, so controllers must
// verify ownership explicitly rather than trusting the scope alone.
trait VerifiesTenantOwnership
{
    protected function ensureBelongsToMerchant(Model $model, Merchant $merchant): void
    {
        abort_unless($model->merchant_id === $merchant->id, 404);
    }
}
