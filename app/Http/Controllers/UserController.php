<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view users')->only(['index']);
        $this->middleware('permission:create users')->only(['create', 'store']);
        // edit/update/destroy pending implementation
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil semua karyawan internal (termasuk teknisi/noc)
        // Kita whitelist role yang dianggap sebagai pegawai internal
        $internalRoles = ['super-admin', 'admin', 'sales', 'finance', 'noc', 'warehouse', 'hrd'];

        $users = User::with('roles')->whereHas('roles', function ($q) use ($internalRoles) {
            $q->whereIn('name', $internalRoles);
        })->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Ambil semua role internal
        $internalRoles = ['super-admin', 'admin', 'sales', 'finance', 'noc', 'warehouse', 'hrd'];
        $roles = \Spatie\Permission\Models\Role::whereIn('name', $internalRoles)->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'Karyawan berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
