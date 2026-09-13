<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanChange extends Model
{
    protected $fillable = [
        'subscription_id',
        'old_plan_id',
        'new_plan_id',
        'changed_at',
        'effective_date',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
            'effective_date' => 'date',
        ];
    }

    // Relationships
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function oldPlan()
    {
        return $this->belongsTo(Plan::class, 'old_plan_id');
    }

    public function newPlan()
    {
        return $this->belongsTo(Plan::class, 'new_plan_id');
    }
}
