<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // HasApiTokens sudah dihapus dari sini karena tidak digunakan
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relasi ke model Role
     */
    public function role()
    {
        return $this->belongsTo(\App\Models\Role::class);
    }
    public function transctions()
    {
        return $this->hasMany(\App\Models\Transaction::class);
    }
}