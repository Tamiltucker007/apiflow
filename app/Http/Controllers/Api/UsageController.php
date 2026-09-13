<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RecordUsageRequest;
use App\Services\UsageService;
use Illuminate\Http\JsonResponse;

class UsageController extends Controller
{
    public function __construct(private UsageService $usage) {}

    public function store(RecordUsageRequest $request): JsonResponse
    {
        $customer = $request->attributes->get('apiCustomer');

        $result = $this->usage->recordUsage(
            $customer,
            $request->validated('event_key'),
            $request->validated('units'),
            $request->validated('recorded_date'),
            $request->validated('metadata'),
        );

        return response()->json(
            ['event' => $result['event'], 'created' => $result['created']],
            $result['created'] ? 201 : 200
        );
    }
}
