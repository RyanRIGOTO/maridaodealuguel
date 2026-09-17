<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(): JsonResponse
    {
        $categorias = Categoria::withCount('servicos')->where('status', 'ativo')->get();

        return response()->json([
            'success' => true,
            'data' => CategoriaResource::collection($categorias),
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $categorias = Categoria::withCount('servicos')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => CategoriaResource::collection($categorias),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome_categoria' => 'required|string|max:100|unique:categorias,nome_categoria',
            'status' => 'nullable|in:ativo,inativo',
        ]);

        $categoria = Categoria::create($data);
        AuditLogService::log('categoria_criada', 'categorias', $categoria->id);

        return response()->json([
            'success' => true,
            'data' => new CategoriaResource($categoria),
            'message' => 'Categoria criada com sucesso.',
        ], 201);
    }

    public function update(Request $request, Categoria $categoria): JsonResponse
    {
        $data = $request->validate([
            'nome_categoria' => 'required|string|max:100|unique:categorias,nome_categoria,' . $categoria->id,
            'status' => 'required|in:ativo,inativo',
        ]);

        $categoria->update($data);
        AuditLogService::log('categoria_editada', 'categorias', $categoria->id);

        return response()->json([
            'success' => true,
            'data' => new CategoriaResource($categoria),
            'message' => 'Categoria atualizada com sucesso.',
        ]);
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        AuditLogService::log('categoria_removida', 'categorias', $categoria->id);
        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoria removida com sucesso.',
        ]);
    }
}
