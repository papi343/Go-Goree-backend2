<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Users\StoreUserRequest;
use App\Http\Requests\Api\V1\Users\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Response;

/**
 * Contrôleur pour gérer les utilisateurs de la plateforme (CRUD administratif).
 */
class UserController extends Controller
{
    /**
     * Liste des utilisateurs.
     */
    public function index()
    {
        return UserResource::collection(User::with(['role', 'portefeuille'])->paginate());
    }

    /**
     * Créer un nouvel utilisateur.
     */
    public function store(StoreUserRequest $request)
    {
        $record = User::create($request->validated());

        return (new UserResource($record->load('role')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Afficher les détails d'un utilisateur spécifique.
     */
    public function show($id)
    {
        $record = User::with('role')->findOrFail($id);

        return new UserResource($record);
    }

    /**
     * Mettre à jour un utilisateur.
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $record = User::findOrFail($id);
        $record->update($request->validated());

        return new UserResource($record->load('role'));
    }

    /**
     * Supprimer un utilisateur.
     */
    public function destroy($id)
    {
        $record = User::findOrFail($id);
        $record->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
