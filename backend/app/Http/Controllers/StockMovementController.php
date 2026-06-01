<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\WarehouseService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(protected WarehouseService $warehouseService)
    {
        $this->middleware('permission:stock-movement-view')->only(['index', 'show']);
        $this->middleware('permission:stock-movement-create')->only([
            'inbound',
            'outbound',
            'transfer',
            'allocateProject',
        ]);
    }

    public function index(Request $request)
    {
        $movements = $this->warehouseService->getPaginatedStockMovements(
            $request->all(),
            $this->resolvePerPage($request)
        );

        return StockMovementResource::collection($movements)->additional([
            'message' => 'Stock movements retrieved successfully',
        ]);
    }

    public function show(StockMovement $stockMovement)
    {
        $stockMovement = $this->warehouseService->getStockMovementDetail($stockMovement);

        return (new StockMovementResource($stockMovement))->additional([
            'message' => 'Stock movement retrieved successfully',
        ]);
    }

    public function inbound(StockMovementRequest $request)
    {
        $movement = $this->warehouseService->recordInbound($request->validated());

        return response()->json([
            'message' => 'Inbound stock movement recorded successfully',
            'data' => new StockMovementResource($movement),
        ], 201);
    }

    public function outbound(StockMovementRequest $request)
    {
        $movement = $this->warehouseService->recordOutbound($request->validated());

        return response()->json([
            'message' => 'Outbound stock movement recorded successfully',
            'data' => new StockMovementResource($movement),
        ], 201);
    }

    public function transfer(StockMovementRequest $request)
    {
        $movement = $this->warehouseService->transferStock($request->validated());

        return response()->json([
            'message' => 'Stock transfer recorded successfully',
            'data' => new StockMovementResource($movement),
        ], 201);
    }

    public function allocateProject(StockMovementRequest $request)
    {
        $movement = $this->warehouseService->allocateToProject($request->validated());

        return response()->json([
            'message' => 'Project material allocation recorded successfully',
            'data' => new StockMovementResource($movement),
        ], 201);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = $request->integer('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }
}
