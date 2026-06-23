<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Traits\FileHelperTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    use FileHelperTrait;

    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => (string) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'username' => (string) $user->username,
            'phone' => $user->phone ? (string) $user->phone : null,
            'role' => $user->role ? [
                'id' => (string) $user->role->id,
                'name' => (string) $user->role->name,
            ] : ['id' => null, 'name' => null],
            'created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
            'updated_at' => $user->updated_at ? $user->updated_at->toDateTimeString() : null,
        ];
    }
}
