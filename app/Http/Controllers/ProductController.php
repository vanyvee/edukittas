<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Http\Requests\Category\CategoryRequest;
use App\Http\Requests\SubCategory\SubCategoryRequest;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Requests\Material\MaterialRequest;
use App\Http\Requests\Material\RestockMaterialRequest;
use App\Http\Requests\Recipe\SyncRecipeRequest;

class ProductController extends Controller
{
    public function __construct(protected ProductService $service) {}


    // ─────────────────────────────────────────
    // CATEGORIES
    // ─────────────────────────────────────────

    public function getCategories()
    {
        return response()->json($this->service->allCategories());
    }

    public function storeCategory(CategoryRequest $request)
    {
        return response()->json($this->service->createCategory($request->validated()), 201);
    }

    public function updateCategory(CategoryRequest $request, int $id)
    {
        return response()->json(['updated' => $this->service->updateCategory($id, $request->validated())]);
    }

    public function destroyCategory(int $id)
    {
        return response()->json(['deleted' => $this->service->deleteCategory($id)]);
    }


    // ─────────────────────────────────────────
    // SUBCATEGORIES
    // ─────────────────────────────────────────

    public function getSubCategories()
    {
        return response()->json($this->service->allSubCategories());
    }

    public function getSubCategoriesByCategory(int $categoryId)
    {
        return response()->json($this->service->subCategoriesByCategory($categoryId));
    }

    public function storeSubCategory(SubCategoryRequest $request)
    {
        return response()->json($this->service->createSubCategory($request->validated()), 201);
    }

    public function updateSubCategory(SubCategoryRequest $request, int $id)
    {
        return response()->json(['updated' => $this->service->updateSubCategory($id, $request->validated())]);
    }

    public function destroySubCategory(int $id)
    {
        return response()->json(['deleted' => $this->service->deleteSubCategory($id)]);
    }


    // ─────────────────────────────────────────
    // PRODUCTS
    // ─────────────────────────────────────────

    public function getProducts()
    {
        return response()->json($this->service->allProducts());
    }

    public function getAvailableProducts()
    {
        return response()->json($this->service->availableProducts());
    }

    public function showProduct(int $id)
    {
        $product = $this->service->findProduct($id);
        if (!$product) return response()->json(['message' => 'Product not found'], 404);
        return response()->json($product);
    }

    public function storeProduct(ProductRequest $request)
    {
        return response()->json($this->service->createProduct($request->validated()), 201);
    }

    public function updateProduct(ProductRequest $request, int $id)
    {
        return response()->json(['updated' => $this->service->updateProduct($id, $request->validated())]);
    }

    public function destroyProduct(int $id)
    {
        return response()->json(['deleted' => $this->service->deleteProduct($id)]);
    }

    public function toggleAvailability(int $id)
    {
        return response()->json(['toggled' => $this->service->toggleAvailability($id)]);
    }


    // ─────────────────────────────────────────
    // RAW MATERIALS
    // ─────────────────────────────────────────

    public function getMaterials()
    {
        return response()->json($this->service->allMaterials());
    }

    public function getLowStock()
    {
        return response()->json($this->service->lowStockMaterials());
    }

    public function storeMaterial(MaterialRequest $request)
    {
        return response()->json($this->service->createMaterial($request->validated()), 201);
    }

    public function updateMaterial(MaterialRequest $request, int $id)
    {
        return response()->json(['updated' => $this->service->updateMaterial($id, $request->validated())]);
    }

    public function destroyMaterial(int $id)
    {
        return response()->json(['deleted' => $this->service->deleteMaterial($id)]);
    }

    public function restock(RestockMaterialRequest $request, int $id)
    {
        $done = $this->service->restockMaterial(
            $id,
            $request->validated('qty'),
            auth()->id(),
            $request->validated('note')
        );

        return response()->json(['restocked' => $done]);
    }


    // ─────────────────────────────────────────
    // RECIPES
    // ─────────────────────────────────────────

    public function getRecipe(int $productId)
    {
        return response()->json($this->service->getRecipe($productId));
    }

    public function syncRecipe(SyncRecipeRequest $request, int $productId)
    {
        $this->service->syncRecipe($productId, $request->validated('materials'));
        return response()->json(['message' => 'Recipe saved']);
    }

    public function removeRecipeItem(int $productId, int $materialId)
    {
        return response()->json(['deleted' => $this->service->removeRecipeItem($productId, $materialId)]);
    }
}