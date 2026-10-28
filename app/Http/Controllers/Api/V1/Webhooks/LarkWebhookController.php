<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Lark\LarkWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LarkWebhookController extends Controller
{
    public function __construct(
        protected LarkWebhookService $webhookService
    ) {}

    /**
     * Handle incoming Lark event or callback webhook request.
     */
    public function handle(Request $request): JsonResponse
    {
        return $this->webhookService->handle($request);
    }
}
