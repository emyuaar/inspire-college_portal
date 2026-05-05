<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::with(['role', 'detail'])->orderByDesc('id');

        if ($search = $request->get('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%");
            });
        }

        return $q->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'role_id'  => ['nullable', 'exists:roles,id'],
            'org_id'   => ['nullable', 'integer'],
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'contact'  => ['nullable', 'string', 'max:20'],

            // optional nested detail
            'detail.gender'  => ['nullable', 'string', 'max:20'],
            'detail.d_o_b'   => ['nullable', 'date'],
            'detail.address' => ['nullable', 'string'],
            'detail.country' => ['nullable', 'string', 'max:100'],
            'detail.city'    => ['nullable', 'string', 'max:100'],
            'detail.state'   => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'role_id'  => $data['role_id'] ?? null,
            'org_id'   => $data['org_id'] ?? null,
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'contact'  => $data['contact'] ?? null,
        ]);

        if (!empty($data['detail'])) {
            $user->detail()->create($data['detail']);
        }

        return $user->load(['role', 'detail']);
    }

    public function show(User $user)
    {
        return $user->load(['role', 'detail']);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id'  => ['nullable', 'exists:roles,id'],
            'org_id'   => ['nullable', 'integer'],
            'name'     => ['sometimes', 'required', 'string', 'max:255'],
            'email'    => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'contact'  => ['nullable', 'string', 'max:20'],

            'detail.gender'  => ['nullable', 'string', 'max:20'],
            'detail.d_o_b'   => ['nullable', 'date'],
            'detail.address' => ['nullable', 'string'],
            'detail.country' => ['nullable', 'string', 'max:100'],
            'detail.city'    => ['nullable', 'string', 'max:100'],
            'detail.state'   => ['nullable', 'string', 'max:100'],
        ]);

        // Update user
        $payload = array_intersect_key($data, array_flip(['role_id','org_id','name','email','contact']));
        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        $user->update($payload);

        // Upsert detail
        if (array_key_exists('detail', $data)) {
            $user->detail()->updateOrCreate(['user_id' => $user->id], $data['detail'] ?? []);
        }

        return $user->load(['role', 'detail']);
    }

    public function destroy(User $user)
    {
        $user->delete(); // soft delete
        return response()->noContent();
    }
}
