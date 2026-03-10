<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'PT Coffee Beans Indonesia', 'phone' => '021-5551234'],
            ['name' => 'CV Dairy Fresh', 'phone' => '021-5552345'],
            ['name' => 'UD Bakery Supplies', 'phone' => '021-5553456'],
            ['name' => 'PT Food Distributor', 'phone' => '021-5554567'],
            ['name' => 'CV Packaging Solutions', 'phone' => '021-5555678'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['name' => $supplier['name']],
                ['phone' => $supplier['phone']]
            );
        }
    }
}
