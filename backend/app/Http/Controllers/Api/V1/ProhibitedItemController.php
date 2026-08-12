<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProhibitedItem;
use App\Models\ProhibitedItemsVersion;
use Illuminate\Http\JsonResponse;

class ProhibitedItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = ProhibitedItem::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'description']);

        $versionId = ProhibitedItemsVersion::query()
            ->orderByDesc('published_at')
            ->value('id');

        return $this->success([
            'items' => $items,
            'version_id' => $versionId,
        ]);
    }
}
