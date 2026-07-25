<?php

namespace App\Http\Controllers;

use App\Actions\Orders\GenerateOrderInsight;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerateOrderInsightController extends Controller
{
    public function __construct(protected GenerateOrderInsight $generateOrderInsight) {}

    /**
     * Summarise the order, suggest next actions, flag gaps and draft a reply.
     */
    public function __invoke(Request $request, Order $order): JsonResponse
    {
        $insight = $this->generateOrderInsight->execute($order, $request->boolean('refresh'));

        return response()->json([
            'insight' => $insight,
            'generated_at' => $order->ai_insight_generated_at?->toIso8601String(),
        ]);
    }
}
