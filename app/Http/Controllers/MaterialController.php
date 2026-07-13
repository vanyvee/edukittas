<?php

n<?php

namespace App\Http\Controllers;

use App\Services\MaterialService;
use App\Models\Material;
use App\Http\Requests\MaterialRequest;
use App\Http\Requests\RestockMaterialRequest;

class MaterialController extends Controller
{
    public function __construct(protected MaterialService $service) {}

    public function index()
    {
        return $this->service->list();
    }

    public function store(MaterialRequest $request)
    {
        $material = $this->service->create($request->validated());
        return response()->json($material->load('logs'), 201);
    }

    public function update(MaterialRequest $request, Material $material)
    {
        $material = $this->service->update($material, $request->validated());
        return response()->json($material->load('logs'));
    }

    public function destroy(Material $material)
    {
        $this->service->delete($material);
        return response()->json(['message' => 'deleted']);
    }

    public function restock(RestockMaterialRequest $request, Material $material)
    {
        $material = $this->service->restock($material, $request->validated());
        return response()->json($material->load('logs'));
    }
}