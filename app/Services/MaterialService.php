<?php

namespace App\Services;

use App\Models\Material;

class MaterialService
{
    public function list()
    {
        return Material::with('logs')->orderBy('name')->get();
    }

    public function create(array $data): Material
    {
        return Material::create($data);
    }

    public function update(Material $material, array $data): Material
    {
        $material->update($data);
        return $material;
    }

    public function delete(Material $material): void
    {
        $material->delete();
    }

    public function restock(Material $material, array $data): Material
    {
        $material->increment('stock', $data['qty']);
        $material->logs()->create([
            'qty'  => $data['qty'],
            'note' => $data['note'] ?? null,
        ]);

        return $material->fresh();
    }
}