<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct(protected WarehouseService $warehouseService)
    {
        $this->middleware('permission:warehouse-view')->only(['index', 'show']);
        $this->middleware('permission:warehouse-create')->only(['store']);
        $this->middleware('permission:warehouse-update')->only(['update']);
        $this->middleware('permission:warehouse-delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $warehouses = $this->warehouseService->getPaginatedWarehouses(
            $request->all(),
            $this->resolvePerPage($request)
        );

        return WarehouseResource::collection($warehouses)->additional([
            'message' => 'Warehouses retrieved successfully',
        ]);
    }

    public function store(WarehouseRequest $request)
    {
        $warehouse = $this->warehouseService->createWarehouse($request->validated());

        return response()->json([
            'message' => 'Warehouse created successfully',
            'data' => new WarehouseResource($warehouse),
        ], 201);
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse = $this->warehouseService->getWarehouseDetail($warehouse);

        return (new WarehouseResource($warehouse))->additional([
            'message' => 'Warehouse retrieved successfully',
        ]);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        $warehouse = $this->warehouseService->updateWarehouse($warehouse, $request->validated());

        return response()->json([
            'message' => 'Warehouse updated successfully',
            'data' => new WarehouseResource($warehouse),
        ]);
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->warehouseService->deleteWarehouse($warehouse);

        return response()->json([
            'message' => 'Warehouse deleted successfully',
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }
}
