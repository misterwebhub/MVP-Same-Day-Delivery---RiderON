<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SupportFaqController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(config('faq.items', []));
    }
}
