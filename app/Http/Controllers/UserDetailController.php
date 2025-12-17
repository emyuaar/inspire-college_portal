<?php

namespace App\Http\Controllers;

use App\Models\UserDetail;
use Illuminate\Http\Request;

class UserDetailController extends Controller
{
    public function index()
    {
        return UserDetail::with('user')->orderByDesc('id')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'gender'  => ['nullable', 'string', 'max:20'],
            'd_o_b'   => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:100'],
            'city'    => ['nullable', 'string', 'max:100'],
            'state'   => ['nullable', 'string', 'max:100'],
        ]);

        return UserDetail::create($data)->load('user');
    }

    public function show(UserDetail $userDetail)
    {
        return $userDetail->load('user');
    }

    public function update(Request $request, UserDetail $userDetail)
    {
        $data = $request->validate([
            'gender'  => ['nullable', 'string', 'max:20'],
            'd_o_b'   => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:100'],
            'city'    => ['nullable', 'string', 'max:100'],
            'state'   => ['nullable', 'string', 'max:100'],
        ]);

        $userDetail->update($data);
        return $userDetail->load('user');
    }

    public function destroy(UserDetail $userDetail)
    {
        $userDetail->delete(); // soft delete
        return response()->noContent();
    }
}
