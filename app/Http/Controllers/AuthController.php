<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Menampilkan Form Login
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Memproses Aksi Login Pengguna
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // PENGALIHAN OTOMATIS BERDASARKAN ROLE
            if (Auth::user()->role->name === 'Admin') {
                // Admin langsung masuk ke Panel Utama Dashboard
                return redirect()->route('dashboard')->with('success', 'Login Berhasil! Selamat Datang di Panel Kendali Admin.');
            }

            // User biasa langsung diarahkan ke Etalase Toko untuk belanja
            return redirect()->route('landing')->with('success', 'Login Berhasil! Selamat berbelanja.');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Menampilkan Form Registrasi Anggota Baru
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Memproses Aksi Registrasi Anggota Baru
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Cari ID Role untuk Pembeli / User Biasa
        $userRole = Role::where('name', 'User')->first();

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $userRole ? $userRole->id : 2, // Default ke ID 2 jika tidak ditemukan
        ]);

        return redirect()->route('login')->with('success', 'Pendaftaran akun berhasil! Silakan masuk.');
    }

    /**
     * Memproses Aksi Keluar (Logout)
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}