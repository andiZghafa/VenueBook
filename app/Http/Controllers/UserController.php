<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::with('destination')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(['role' => 'sales']),
            'destinations' => Destination::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request, passwordRequired: true);

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('users.index')->with('success', "Akun \"{$data['name']}\" berhasil dibuat.");
    }

    public function edit(Request $request, User $user): View
    {
        abort_if(! $request->user()->isSuperAdmin() && $user->isSuperAdmin(), 403, 'Anda tidak dapat mengedit Super Admin.');

        return view('users.form', [
            'user' => $user,
            'destinations' => Destination::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if(! $request->user()->isSuperAdmin() && $user->isSuperAdmin(), 403, 'Anda tidak dapat mengedit Super Admin.');

        $data = $this->validateUser($request, passwordRequired: false, ignoreUserId: $user->id);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', "Akun \"{$user->name}\" berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Anda tidak dapat menghapus akun Anda sendiri.');
        abort_if(! $request->user()->isSuperAdmin() && $user->isSuperAdmin(), 403, 'Anda tidak dapat menghapus Super Admin.');

        $user->delete();

        return redirect()->route('users.index')->with('success', "Akun \"{$user->name}\" berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, bool $passwordRequired, ?int $ignoreUserId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$ignoreUserId],
            'role' => ['required', 'in:superadmin,admin,sales'],
            'destination_id' => ['nullable', 'required_if:role,sales', 'exists:destinations,id'],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'min:8'],
        ]);

        if (! $request->user()->isSuperAdmin() && $data['role'] === 'superadmin') {
            abort(403, 'Anda tidak dapat memberikan peran Super Admin.');
        }

        if (in_array($data['role'], ['admin', 'superadmin'])) {
            $data['destination_id'] = null;
        }

        return $data;
    }
}
