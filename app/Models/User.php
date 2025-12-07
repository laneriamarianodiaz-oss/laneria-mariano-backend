<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ===================================
    // 👤 MÉTODOS DE ROLES
    // ===================================

    public function esAdministrador()
    {
        return $this->rol === 'administrador';
    }

    public function esVendedor()
    {
        return $this->rol === 'vendedor';
    }

    public function esCliente()
    {
        return $this->rol === 'cliente';
    }

    // ===================================
    // 🔍 SCOPES
    // ===================================

    public function scopeAdministradores($query)
    {
        return $query->where('rol', 'administrador');
    }

    public function scopeVendedores($query)
    {
        return $query->where('rol', 'vendedor');
    }

    // ===================================
    // 🔗 RELACIONES
    // ===================================

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'user_id', 'id');
    }
}