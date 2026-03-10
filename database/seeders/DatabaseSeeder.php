<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Staff;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductCost;
use App\Models\MembershipTier;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Payment Methods
        $paymentMethods = ['Cash', 'QRIS', 'Bank Transfer', 'E-Wallet', 'Debit Card', 'Credit Card'];
        foreach ($paymentMethods as $method) {
            PaymentMethod::create(['name' => $method, 'is_active' => true]);
        }

        // Staff (Default Admin/Cashier)
        Staff::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        Staff::create([
            'name' => 'Cashier 1',
            'email' => 'cashier1@example.com',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // Product Categories
        $coffeeCategory = ProductCategory::create(['name' => 'Coffee']);
        $nonCoffeeCategory = ProductCategory::create(['name' => 'Non-Coffee']);
        $foodCategory = ProductCategory::create(['name' => 'Food']);

        // Products
        $products = [
            ['name' => 'Americano', 'sku' => 'AME001', 'category' => $coffeeCategory, 'price' => 25000, 'cost' => 12000],
            ['name' => 'Latte', 'sku' => 'LAT001', 'category' => $coffeeCategory, 'price' => 30000, 'cost' => 15000],
            ['name' => 'Cappuccino', 'sku' => 'CAP001', 'category' => $coffeeCategory, 'price' => 30000, 'cost' => 15000],
            ['name' => 'Espresso', 'sku' => 'ESP001', 'category' => $coffeeCategory, 'price' => 20000, 'cost' => 10000],
            ['name' => 'Mocha', 'sku' => 'MOC001', 'category' => $coffeeCategory, 'price' => 35000, 'cost' => 18000],
            ['name' => 'Matcha Latte', 'sku' => 'MAT001', 'category' => $nonCoffeeCategory, 'price' => 32000, 'cost' => 16000],
            ['name' => 'Chocolate', 'sku' => 'CHO001', 'category' => $nonCoffeeCategory, 'price' => 28000, 'cost' => 14000],
            ['name' => 'Croissant', 'sku' => 'CRO001', 'category' => $foodCategory, 'price' => 25000, 'cost' => 12000],
            ['name' => 'Sandwich', 'sku' => 'SAN001', 'category' => $foodCategory, 'price' => 35000, 'cost' => 18000],
            ['name' => 'Cake Slice', 'sku' => 'CAK001', 'category' => $foodCategory, 'price' => 40000, 'cost' => 20000],
        ];

        foreach ($products as $product) {
            $p = Product::create([
                'name' => $product['name'],
                'sku' => $product['sku'],
                'category_id' => $product['category']->id,
                'is_active' => true,
            ]);

            ProductPrice::create([
                'product_id' => $p->id,
                'price' => $product['price'],
                'effective_from' => now(),
            ]);

            ProductCost::create([
                'product_id' => $p->id,
                'cost' => $product['cost'],
                'effective_from' => now(),
            ]);
        }

        // Membership Tiers
        MembershipTier::create(['name' => 'Bronze', 'min_spend' => 0, 'points_multiplier' => 1]);
        MembershipTier::create(['name' => 'Silver', 'min_spend' => 500000, 'points_multiplier' => 2]);
        MembershipTier::create(['name' => 'Gold', 'min_spend' => 2000000, 'points_multiplier' => 3]);
        MembershipTier::create(['name' => 'VIP', 'min_spend' => 5000000, 'points_multiplier' => 5]);

        // Expense Categories
        $expenseCategories = ['rent', 'salary', 'utilities', 'supplies', 'inventory_purchase'];
        foreach ($expenseCategories as $category) {
            ExpenseCategory::create(['name' => $category]);
        }

        // Suppliers
        $suppliers = [
            ['name' => 'PT Coffee Beans Indonesia', 'phone' => '021-5551234'],
            ['name' => 'CV Dairy Fresh', 'phone' => '021-5552345'],
            ['name' => 'UD Bakery Supplies', 'phone' => '021-5553456'],
            ['name' => 'PT Food Distributor', 'phone' => '021-5554567'],
            ['name' => 'CV Packaging Solutions', 'phone' => '021-5555678'],
        ];
        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
