<?php

namespace App\Services;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RestockLog;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class ProductService
{

    // ─────────────────────────────────────────
    // CATEGORIES
    // ─────────────────────────────────────────

    public function allCategories()
    {
        return Category::orderBy('sort_order')->get();
    }

    public function createCategory(array $data): Category
    {
        return Category::create($data);
    }

    public function updateCategory(int $id, array $data): bool
    {
        return Category::where('id', $id)->update($data) > 0;
    }

    public function deleteCategory(int $id): bool
    {
        return Category::destroy($id) > 0;
    }


    // ─────────────────────────────────────────
    // SUBCATEGORIES
    // ─────────────────────────────────────────

    public function allSubCategories()
    {
        return SubCategory::with('category')->orderBy('sort_order')->get();
    }

    public function subCategoriesByCategory(int $categoryId)
    {
        return SubCategory::where('category_id', $categoryId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createSubCategory(array $data): SubCategory
    {
        return SubCategory::create($data);
    }

    public function updateSubCategory(int $id, array $data): bool
    {
        return SubCategory::where('id', $id)->update($data) > 0;
    }

    public function deleteSubCategory(int $id): bool
    {
        return SubCategory::destroy($id) > 0;
    }


    // ─────────────────────────────────────────
    // PRODUCTS
    // ─────────────────────────────────────────

    public function allProducts()
    {
        return Product::with('subCategory.category')
            ->orderBy('sort_order')
            ->get();
    }

    public function availableProducts()
    {
        return Product::where('available', true)
            ->with('subCategory.category')
            ->orderBy('sort_order')
            ->get();
    }

    public function findProduct(int $id): ?Product
    {
        return Product::with('subCategory.category', 'recipes.material')->find($id);
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(int $id, array $data): bool
    {
        return Product::where('id', $id)->update($data) > 0;
    }

    public function deleteProduct(int $id): bool
    {
        return Product::destroy($id) > 0;
    }

    public function toggleAvailability(int $id): bool
    {
        $product = Product::find($id);
        if (!$product) return false;
        $product->available = !$product->available;
        return $product->save();
    }


    // ─────────────────────────────────────────
    // RAW MATERIALS
    // ─────────────────────────────────────────

    public function allMaterials()
    {
        return RawMaterial::orderBy('name')->get();
    }

    public function lowStockMaterials()
    {
        return RawMaterial::whereColumn('stock', '<=', 'threshold')->get();
    }

    public function findMaterial(int $id): ?RawMaterial
    {
        return RawMaterial::find($id);
    }

    public function createMaterial(array $data): RawMaterial
    {
        return RawMaterial::create($data);
    }

    public function updateMaterial(int $id, array $data): bool
    {
        return RawMaterial::where('id', $id)->update($data) > 0;
    }

    public function deleteMaterial(int $id): bool
    {
        return RawMaterial::destroy($id) > 0;
    }

    public function restockMaterial(int $id, float $qty, int $userId, ?string $note): bool
    {
        return DB::transaction(function () use ($id, $qty, $userId, $note) {
            $material = RawMaterial::lockForUpdate()->find($id);
            if (!$material) return false;

            $before = $material->stock;
            $material->stock += $qty;
            $material->save();

            RestockLog::create([
                'material_id'  => $id,
                'user_id'      => $userId,
                'qty_added'    => $qty,
                'stock_before' => $before,
                'stock_after'  => $material->stock,
                'note'         => $note,
            ]);

            return true;
        });
    }

    public function deductStock(int $productId, int $qty = 1): bool
    {
        return DB::transaction(function () use ($productId, $qty) {
            $recipes = Recipe::where('product_id', $productId)->get();

            foreach ($recipes as $recipe) {
                $material = RawMaterial::lockForUpdate()->find($recipe->material_id);
                if (!$material) continue;

                $needed = $recipe->qty * $qty;
                if ($material->stock < $needed) return false;

                $material->stock -= $needed;
                $material->save();
            }

            return true;
        });
    }


    // ─────────────────────────────────────────
    // RECIPES
    // ─────────────────────────────────────────

    public function getRecipe(int $productId)
    {
        return Recipe::with('material')
            ->where('product_id', $productId)
            ->get();
    }

    public function syncRecipe(int $productId, array $materials): void
    {
        // $materials = [['material_id' => 1, 'qty' => 0.5], ...]
        DB::transaction(function () use ($productId, $materials) {
            Recipe::where('product_id', $productId)->delete();

            foreach ($materials as $item) {
                Recipe::create([
                    'product_id'  => $productId,
                    'material_id' => $item['material_id'],
                    'qty'         => $item['qty'],
                ]);
            }
        });
    }

    public function removeRecipeItem(int $productId, int $materialId): bool
    {
        return Recipe::where('product_id', $productId)
            ->where('material_id', $materialId)
            ->delete() > 0;
    }
}