<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Seed categories, warehouses, items, inventories,
     * stock movements, and project materials.
     */
    public function run(): void
    {
        // ── 1. Categories ────────────────────────────────────────────
        $itemCategories = [
            'Solar Panel',
            'Inverter',
            'Baterai',
            'Kabel',
            'Mounting',
            'Controller',
            'Lampu LED',
            'Tiang',
            'Aksesoris',
            'Alat Ukur',
        ];

        foreach ($itemCategories as $name) {
            Category::firstOrCreate(
                ['name' => $name, 'type' => 'item'],
            );
        }

        $this->command->info('Item categories seeded: ' . count($itemCategories));

        // ── 2. Warehouses ────────────────────────────────────────────
        $warehouses = [
            ['name' => 'Gudang Utama Bogor', 'location' => 'Ruko Taman Yasmin Sektor 6, Bogor'],
            ['name' => 'Gudang Transit Jakarta', 'location' => 'Jl. Raya Cilandak KKO, Jakarta Selatan'],
            ['name' => 'Gudang Proyek Bandung', 'location' => 'Jl. Soekarno-Hatta No. 225, Bandung'],
            ['name' => 'Gudang Proyek Surabaya', 'location' => 'Jl. Rungkut Industri III No. 10, Surabaya'],
            ['name' => 'Gudang Proyek Semarang', 'location' => 'Jl. Gatot Subroto No. 5, Semarang'],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::firstOrCreate(['name' => $wh['name']], $wh);
        }

        $this->command->info('Warehouses seeded: ' . count($warehouses));

        // ── 3. Items ─────────────────────────────────────────────────
        $categoryMap = Category::where('type', 'item')->pluck('id', 'name');

        $items = [
            ['sku' => 'ITM-0001-SP450', 'category' => 'Solar Panel', 'name' => 'Solar Panel 450W Monocrystalline', 'unit' => 'unit', 'minimum_stock' => 50],
            ['sku' => 'ITM-0002-SP550', 'category' => 'Solar Panel', 'name' => 'Solar Panel 550W Bifacial', 'unit' => 'unit', 'minimum_stock' => 30],
            ['sku' => 'ITM-0003-SP330', 'category' => 'Solar Panel', 'name' => 'Solar Panel 330W Polycrystalline', 'unit' => 'unit', 'minimum_stock' => 40],
            ['sku' => 'ITM-0004-IH5K', 'category' => 'Inverter', 'name' => 'Inverter Hybrid 5kW', 'unit' => 'unit', 'minimum_stock' => 10],
            ['sku' => 'ITM-0005-IO10', 'category' => 'Inverter', 'name' => 'Inverter On-grid 10kW', 'unit' => 'unit', 'minimum_stock' => 8],
            ['sku' => 'ITM-0006-IO3K', 'category' => 'Inverter', 'name' => 'Inverter Off-grid 3kW', 'unit' => 'unit', 'minimum_stock' => 10],
            ['sku' => 'ITM-0007-BL10', 'category' => 'Baterai', 'name' => 'Baterai Lithium 100Ah', 'unit' => 'unit', 'minimum_stock' => 20],
            ['sku' => 'ITM-0008-BL20', 'category' => 'Baterai', 'name' => 'Baterai Lithium 200Ah', 'unit' => 'unit', 'minimum_stock' => 15],
            ['sku' => 'ITM-0009-BV15', 'category' => 'Baterai', 'name' => 'Baterai VRLA 150Ah', 'unit' => 'unit', 'minimum_stock' => 20],
            ['sku' => 'ITM-0010-KS4M', 'category' => 'Kabel', 'name' => 'Kabel Solar 4mm² PV', 'unit' => 'meter', 'minimum_stock' => 500],
            ['sku' => 'ITM-0011-KS6M', 'category' => 'Kabel', 'name' => 'Kabel Solar 6mm² PV', 'unit' => 'meter', 'minimum_stock' => 300],
            ['sku' => 'ITM-0012-KNY4', 'category' => 'Kabel', 'name' => 'Kabel NYY 4x10mm²', 'unit' => 'meter', 'minimum_stock' => 200],
            ['sku' => 'ITM-0013-MR42', 'category' => 'Mounting', 'name' => 'Mounting Rail Aluminium 4.2m', 'unit' => 'batang', 'minimum_stock' => 100],
            ['sku' => 'ITM-0014-MMC', 'category' => 'Mounting', 'name' => 'Mounting Mid Clamp', 'unit' => 'pcs', 'minimum_stock' => 200],
            ['sku' => 'ITM-0015-MEC', 'category' => 'Mounting', 'name' => 'Mounting End Clamp', 'unit' => 'pcs', 'minimum_stock' => 200],
            ['sku' => 'ITM-0016-CM60', 'category' => 'Controller', 'name' => 'Charge Controller MPPT 60A', 'unit' => 'unit', 'minimum_stock' => 10],
            ['sku' => 'ITM-0017-CP30', 'category' => 'Controller', 'name' => 'Charge Controller PWM 30A', 'unit' => 'unit', 'minimum_stock' => 15],
            ['sku' => 'ITM-0018-LD60', 'category' => 'Lampu LED', 'name' => 'Lampu LED 60W All In One', 'unit' => 'unit', 'minimum_stock' => 30],
            ['sku' => 'ITM-0019-LD10', 'category' => 'Lampu LED', 'name' => 'Lampu LED 100W Two In One', 'unit' => 'unit', 'minimum_stock' => 25],
            ['sku' => 'ITM-0020-LD15', 'category' => 'Lampu LED', 'name' => 'Lampu LED 150W Konvensional', 'unit' => 'unit', 'minimum_stock' => 20],
            ['sku' => 'ITM-0021-TO7M', 'category' => 'Tiang', 'name' => 'Tiang Oktagonal 7m', 'unit' => 'batang', 'minimum_stock' => 15],
            ['sku' => 'ITM-0022-TO9M', 'category' => 'Tiang', 'name' => 'Tiang Oktagonal 9m', 'unit' => 'batang', 'minimum_stock' => 10],
            ['sku' => 'ITM-0023-MC4M', 'category' => 'Aksesoris', 'name' => 'MC4 Connector Male', 'unit' => 'pcs', 'minimum_stock' => 100],
            ['sku' => 'ITM-0024-MC4F', 'category' => 'Aksesoris', 'name' => 'MC4 Connector Female', 'unit' => 'pcs', 'minimum_stock' => 100],
            ['sku' => 'ITM-0025-CB4S', 'category' => 'Aksesoris', 'name' => 'Combiner Box 4 String', 'unit' => 'unit', 'minimum_stock' => 10],
            ['sku' => 'ITM-0026-SPD1', 'category' => 'Aksesoris', 'name' => 'SPD DC 1000V', 'unit' => 'unit', 'minimum_stock' => 15],
            ['sku' => 'ITM-0027-GR58', 'category' => 'Aksesoris', 'name' => 'Grounding Rod 5/8" x 2.4m', 'unit' => 'batang', 'minimum_stock' => 30],
            ['sku' => 'ITM-0028-GC50', 'category' => 'Aksesoris', 'name' => 'Grounding Cable BC 50mm²', 'unit' => 'meter', 'minimum_stock' => 100],
            ['sku' => 'ITM-0029-TM01', 'category' => 'Alat Ukur', 'name' => 'Clamp Meter Digital', 'unit' => 'unit', 'minimum_stock' => 3],
            ['sku' => 'ITM-0030-TM02', 'category' => 'Alat Ukur', 'name' => 'Irradiance Meter', 'unit' => 'unit', 'minimum_stock' => 2],
        ];

        foreach ($items as $itemData) {
            Item::firstOrCreate(
                ['sku' => $itemData['sku']],
                [
                    'sku' => $itemData['sku'],
                    'category_id' => $categoryMap[$itemData['category']] ?? 1,
                    'name' => $itemData['name'],
                    'unit' => $itemData['unit'],
                    'minimum_stock' => $itemData['minimum_stock'],
                ]
            );
        }

        $this->command->info('Items seeded: ' . count($items));

        // ── 4. Inventories (stok awal per warehouse) ─────────────────
        $allItems = Item::all();
        $allWarehouses = Warehouse::all();
        $inventoryCount = 0;

        foreach ($allItems as $item) {
            // Setiap item ada di 1-3 warehouse secara random
            $selectedWarehouses = $allWarehouses->random(min(rand(1, 3), $allWarehouses->count()));

            foreach ($selectedWarehouses as $warehouse) {
                Inventory::firstOrCreate(
                    ['item_id' => $item->id, 'warehouse_id' => $warehouse->id],
                    [
                        'quantity' => rand(10, 300),
                        'placement' => 'Rak ' . chr(rand(65, 70)) . '-' . rand(1, 10),
                    ]
                );
                $inventoryCount++;
            }
        }

        $this->command->info("Inventories seeded: {$inventoryCount}");

        // ── 5. Stock Movements ───────────────────────────────────────
        $inventories = Inventory::with(['item', 'warehouse'])->get();
        $projects = Project::all();
        $movementCount = 0;

        // 5a. Inbound movements (pengadaan awal)
        foreach ($inventories as $inv) {
            StockMovement::create([
                'type' => 'inbound',
                'item_id' => $inv->item_id,
                'source_warehouse_id' => null,
                'destination_warehouse_id' => $inv->warehouse_id,
                'project_id' => null,
                'quantity' => $inv->quantity,
                'placement' => $inv->placement,
                'notes' => 'Stok awal - pengadaan',
                'occurred_at' => now()->subMonths(rand(3, 6))->subDays(rand(0, 30)),
            ]);
            $movementCount++;
        }

        // 5b. Additional inbound movements
        for ($i = 0; $i < 20; $i++) {
            $inv = $inventories->random();
            StockMovement::create([
                'type' => 'inbound',
                'item_id' => $inv->item_id,
                'source_warehouse_id' => null,
                'destination_warehouse_id' => $inv->warehouse_id,
                'project_id' => null,
                'quantity' => rand(10, 80),
                'placement' => $inv->placement ?? ('Rak ' . chr(rand(65, 70)) . '-' . rand(1, 10)),
                'notes' => collect(['Pembelian dari supplier', 'Pengadaan rutin', 'Restock bulanan', 'PO dari vendor'])->random(),
                'occurred_at' => now()->subMonths(rand(1, 3))->subDays(rand(0, 30)),
            ]);
            $movementCount++;
        }

        // 5c. Transfer movements (mutasi antar gudang)
        for ($i = 0; $i < 10; $i++) {
            $inv = $inventories->random();
            $destWarehouse = $allWarehouses->where('id', '!=', $inv->warehouse_id)->random();

            StockMovement::create([
                'type' => 'transfer',
                'item_id' => $inv->item_id,
                'source_warehouse_id' => $inv->warehouse_id,
                'destination_warehouse_id' => $destWarehouse->id,
                'project_id' => null,
                'quantity' => rand(5, 30),
                'placement' => 'Rak ' . chr(rand(65, 70)) . '-' . rand(1, 10),
                'notes' => collect(['Mutasi antar gudang', 'Pemerataan stok', 'Permintaan site'])->random(),
                'occurred_at' => now()->subMonths(rand(0, 2))->subDays(rand(0, 30)),
            ]);
            $movementCount++;
        }

        // 5d. Outbound movements
        for ($i = 0; $i < 8; $i++) {
            $inv = $inventories->random();

            StockMovement::create([
                'type' => 'outbound',
                'item_id' => $inv->item_id,
                'source_warehouse_id' => $inv->warehouse_id,
                'destination_warehouse_id' => null,
                'project_id' => null,
                'quantity' => rand(1, 15),
                'notes' => collect(['Barang rusak/disposal', 'Retur ke vendor', 'Sample pengujian'])->random(),
                'occurred_at' => now()->subDays(rand(1, 60)),
            ]);
            $movementCount++;
        }

        // 5e. Project allocation movements
        $projectAllocationMovements = [];
        if ($projects->isNotEmpty()) {
            for ($i = 0; $i < 25; $i++) {
                $inv = $inventories->random();
                $project = $projects->random();
                $qty = rand(2, 40);

                $movement = StockMovement::create([
                    'type' => 'project_allocation',
                    'item_id' => $inv->item_id,
                    'source_warehouse_id' => $inv->warehouse_id,
                    'destination_warehouse_id' => null,
                    'project_id' => $project->id,
                    'quantity' => $qty,
                    'notes' => 'Alokasi material ke proyek ' . $project->name,
                    'occurred_at' => now()->subDays(rand(1, 90)),
                ]);

                $projectAllocationMovements[] = [
                    'movement' => $movement,
                    'project' => $project,
                    'inv' => $inv,
                    'qty' => $qty,
                ];
                $movementCount++;
            }
        }

        $this->command->info("Stock movements seeded: {$movementCount}");

        // ── 6. Project Materials ─────────────────────────────────────
        $materialCount = 0;

        foreach ($projectAllocationMovements as $data) {
            ProjectMaterial::create([
                'project_id' => $data['project']->id,
                'item_id' => $data['movement']->item_id,
                'warehouse_id' => $data['inv']->warehouse_id,
                'stock_movement_id' => $data['movement']->id,
                'quantity' => $data['qty'],
                'allocated_at' => $data['movement']->occurred_at,
                'notes' => 'Material: ' . $data['inv']->item->name,
            ]);
            $materialCount++;
        }

        $this->command->info("Project materials seeded: {$materialCount}");
    }
}
