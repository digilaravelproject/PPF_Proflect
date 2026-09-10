<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile', ['admin' => Auth::guard('admin')->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('admins')->ignore($admin)], 'phone' => ['nullable', 'string', 'max:20'], 'current_password' => ['nullable', 'required_with:password', 'current_password:admin'], 'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()]]);
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        unset($data['current_password']);
        $admin->update($data);

        return back()->with('status', 'Admin profile updated successfully.');
    }
}
