<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepo
    ) {}

    public function getProducts(array $filters = [], int $page = 1)
    {
        return $this->productRepo->getAll($filters, 20);
    }

    public function getProductById(int $id)
    {
        return $this->productRepo->getById($id);
    }

    public function createProduct(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Create product
            $product = $this->productRepo->create($data);

            // Set initial price if provided
            if (!empty($data['price'])) {
                $this->productRepo->setPrice($product, $data['price']);
            }

            // Set initial cost if provided
            if (!empty($data['cost'])) {
                $this->productRepo->setCost($product, $data['cost']);
            }

            return $product->fresh(['category', 'prices', 'costs']);
        });
    }

    public function updateProduct(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->productRepo->getById($id);
            
            $updatedProduct = $this->productRepo->update($product, $data);

            // Update price if provided
            if (isset($data['price'])) {
                $this->productRepo->setPrice($updatedProduct, $data['price']);
            }

            // Update cost if provided
            if (isset($data['cost'])) {
                $this->productRepo->setCost($updatedProduct, $data['cost']);
            }

            return $updatedProduct->fresh(['category', 'prices', 'costs']);
        });
    }

    public function deleteProduct(int $id)
    {
        $product = $this->productRepo->getById($id);
        return $this->productRepo->delete($product);
    }

    public function getCategories()
    {
        return $this->productRepo->getCategories();
    }

    public function createCategory(string $name)
    {
        return $this->productRepo->createCategory($name);
    }
}
