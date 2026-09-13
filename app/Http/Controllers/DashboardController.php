<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function merchant(Merchant $merchant): View
    {
        return view('merchants.dashboard', [
            'merchant' => $merchant,
            'usage' => $this->dashboard->getCurrentCycleUsage($merchant),
            'projectedOverageRevenue' => $this->dashboard->getProjectedOverageRevenue($merchant),
            'activeSubscriptionCount' => $merchant->subscriptions()->active()->count(),
            'topCustomers' => $this->dashboard->getTopCustomersByUsage($merchant),
            'churnRisk' => $this->dashboard->getChurnRiskCustomers($merchant),
            'usageTrend' => $this->dashboard->getDailyUsageTrend($merchant),
        ]);
    }
}
