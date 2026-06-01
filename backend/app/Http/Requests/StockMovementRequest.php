<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->route()?->getActionMethod()) {
            'inbound' => $this->inboundRules(),
            'outbound' => $this->outboundRules(),
            'transfer' => $this->transferRules(),
            'allocateProject' => $this->projectAllocationRules(),
            default => [],
        };
    }

    private function inboundRules(): array
    {
        return $this->baseStockRules() + [
            'destination_warehouse_id' => $this->warehouseRule(),
            'occurred_at' => $this->optionalDateRule(),
        ];
    }

    private function outboundRules(): array
    {
        return $this->baseStockRules() + [
            'source_warehouse_id' => $this->warehouseRule(),
            'occurred_at' => $this->optionalDateRule(),
        ];
    }

    private function transferRules(): array
    {
        return $this->baseStockRules() + [
            'source_warehouse_id' => $this->warehouseRule(),
            'destination_warehouse_id' => ['required', 'integer', 'different:source_warehouse_id', 'exists:warehouses,id'],
            'occurred_at' => $this->optionalDateRule(),
        ];
    }

    private function projectAllocationRules(): array
    {
        return $this->baseStockRules() + [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'warehouse_id' => $this->warehouseRule(),
            'allocated_at' => $this->optionalDateRule(),
        ];
    }

    private function baseStockRules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function warehouseRule(): array
    {
        return ['required', 'integer', 'exists:warehouses,id'];
    }

    private function optionalDateRule(): array
    {
        return ['nullable', 'date'];
    }
}
