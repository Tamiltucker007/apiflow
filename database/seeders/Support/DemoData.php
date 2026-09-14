<?php

namespace Database\Seeders\Support;

/**
 * Single source of truth for the seeded demo tenants — every seeder loops
 * over merchants() instead of each hardcoding its own merchant lookup.
 */
class DemoData
{
    /**
     * @return array<string, array{
     *     name: string,
     *     description: string,
     *     email: string,
     *     admin_email: string,
     *     theme_from: string, theme_to: string,
     *     plans: list<array{name: string, billing_cycle: string, base_price_cents: int, included_units: int, overage_rate_cents: int}>,
     *     customers: list<array{name: string, email: string, plan: string, daily_units: int, previous_month_multiplier: float, portal_password?: string, plan_change_to?: string}>,
     * }>
     */
    public static function merchants(): array
    {
        return [
            'finpay' => [
                'name' => 'FinPay Technologies',
                'description' => 'Currency Exchange API',
                'email' => 'ops@finpay.test',
                'admin_email' => 'admin@finpay.com',
                // Indigo -> fuchsia: the platform's own default brand.
                'theme_from' => '#4f46e5',
                'theme_to' => '#c026d3',
                'plans' => [
                    ['name' => 'Starter', 'billing_cycle' => 'monthly', 'base_price_cents' => 99900, 'included_units' => 10000, 'overage_rate_cents' => 15],
                    ['name' => 'Growth', 'billing_cycle' => 'monthly', 'base_price_cents' => 499900, 'included_units' => 50000, 'overage_rate_cents' => 10],
                    ['name' => 'Pro', 'billing_cycle' => 'monthly', 'base_price_cents' => 1499900, 'included_units' => 250000, 'overage_rate_cents' => 5],
                ],
                'customers' => [
                    // Started the month on Growth, upgraded to Pro mid-month —
                    // demonstrates the mid-cycle plan change / proration split.
                    ['name' => 'ABC Forex Pvt Ltd', 'email' => 'billing@abcforex.test', 'plan' => 'Growth', 'plan_change_to' => 'Pro', 'portal_password' => 'password', 'daily_units' => 800, 'previous_month_multiplier' => 1.2],
                    // Exceeds its Pro allowance within days — overage demo.
                    ['name' => 'Beta Retail Pvt Ltd', 'email' => 'billing@betaretail.test', 'plan' => 'Pro', 'daily_units' => 30000, 'previous_month_multiplier' => 0.85],
                    ['name' => 'Craft Foods Co.', 'email' => 'billing@craftfoods.test', 'plan' => 'Growth', 'daily_units' => 1000, 'previous_month_multiplier' => 1.15],
                    // >50% drop vs. last month — churn-risk demo.
                    ['name' => 'Nova Traders', 'email' => 'billing@novatraders.test', 'plan' => 'Starter', 'daily_units' => 50, 'previous_month_multiplier' => 6.0],
                    ['name' => 'QuickMart', 'email' => 'billing@quickmart.test', 'plan' => 'Starter', 'daily_units' => 300, 'previous_month_multiplier' => 1.1],
                ],
            ],

            'geolocate' => [
                'name' => 'GeoLocate Pro',
                'description' => 'Location & Geocoding API',
                'email' => 'ops@geolocate.test',
                'admin_email' => 'admin@geolocate.com',
                // Emerald -> teal: maps/location green.
                'theme_from' => '#059669',
                'theme_to' => '#0d9488',
                'plans' => [
                    ['name' => 'Basic', 'billing_cycle' => 'monthly', 'base_price_cents' => 149900, 'included_units' => 20000, 'overage_rate_cents' => 8],
                    ['name' => 'Business', 'billing_cycle' => 'monthly', 'base_price_cents' => 699900, 'included_units' => 100000, 'overage_rate_cents' => 5],
                ],
                'customers' => [
                    ['name' => 'Maply Inc', 'email' => 'billing@maply.test', 'plan' => 'Business', 'daily_units' => 4000, 'previous_month_multiplier' => 1.1],
                    ['name' => 'TrackWise Logistics', 'email' => 'billing@trackwise.test', 'plan' => 'Basic', 'daily_units' => 600, 'previous_month_multiplier' => 1.0],
                    // >50% drop vs. last month — churn-risk demo.
                    ['name' => 'FleetSight', 'email' => 'billing@fleetsight.test', 'plan' => 'Business', 'daily_units' => 200, 'previous_month_multiplier' => 5.0],
                ],
            ],

            'weathercloud' => [
                'name' => 'WeatherCloud',
                'description' => 'Weather Forecast API',
                'email' => 'ops@weathercloud.test',
                'admin_email' => 'admin@weathercloud.com',
                // Sky -> cyan: weather blue.
                'theme_from' => '#0284c7',
                'theme_to' => '#0891b2',
                'plans' => [
                    ['name' => 'Hobby', 'billing_cycle' => 'monthly', 'base_price_cents' => 49900, 'included_units' => 5000, 'overage_rate_cents' => 20],
                    ['name' => 'Enterprise', 'billing_cycle' => 'monthly', 'base_price_cents' => 999900, 'included_units' => 150000, 'overage_rate_cents' => 6],
                ],
                'customers' => [
                    // Exceeds its allowance — overage demo.
                    ['name' => 'AgriForecast', 'email' => 'billing@agriforecast.test', 'plan' => 'Enterprise', 'daily_units' => 6000, 'previous_month_multiplier' => 0.9],
                    ['name' => 'StormWatch Media', 'email' => 'billing@stormwatch.test', 'plan' => 'Hobby', 'daily_units' => 150, 'previous_month_multiplier' => 1.2],
                ],
            ],
        ];
    }
}
