<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(protected ItemService $itemService)
    {
        $this->middleware('permission:item-view')->only(['index', 'show']);
        $this->middleware('permission:item-create')->only(['store']);
        $this->middleware('permission:item-update')->only(['update']);
        $this->middleware('permission:item-delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $items = $this->itemService->getPaginatedItems(
            $request->all(),
            $this->resolvePerPage($request)
        );

        return ItemResource::collection($items)->additional([
            'message' => 'Items retrieved successfully',
        ]);
    }

    public function store(ItemRequest $request)
    {
        $item = $this->itemService->createItem($request->validated());

        return response()->json([
            'message' => 'Item created successfully',
            'data' => new ItemResource($item),
        ], 201);
    }

    public function show(Item $item)
    {
        $item = $this->itemService->getItemDetail($item);

        return (new ItemResource($item))->additional([
            'message' => 'Item retrieved successfully',
        ]);
    }

    public function update(ItemRequest $request, Item $item)
    {
        $item = $this->itemService->updateItem($item, $request->validated());

        return response()->json([
            'message' => 'Item updated successfully',
            'data' => new ItemResource($item),
        ]);
    }

    public function destroy(Item $item)
    {
        $this->itemService->deleteItem($item);

        return response()->json([
            'message' => 'Item deleted successfully',
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }
}
