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
        'verification_code',
        'verification_code_expires_at',
        'password_reset_token',
        'password_reset_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'password_reset_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verification_code_expires_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ===================================
    // 🔐 MÉTODOS DE VERIFICACIÓN
    // ===================================
    
    /**
     * Verificar si el email está verificado
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Marcar email como verificado
     */
    public function markEmailAsVerified()
    {
        $this->email_verified_at = now();
        $this->verification_code = null;
        $this->verification_code_expires_at = null;
        $this->save();
    }

    // ===================================
    // 👤 MÉTODOS DE ROLES
    // ===================================

    /**
     * Verificar si es administrador
     */
    public function esAdministrador()
    {
        return $this->rol === 'administrador';
    }

    /**
     * Verificar si es vendedor
     */
    public function esVendedor()
    {
        return $this->rol === 'vendedor';
    }

    /**
     * Verificar si es cliente
     */
    public function esCliente()
    {
        return $this->rol === 'cliente';
    }

    // ===================================
    // 🔍 SCOPES
    // ===================================

    /**
     * Scope: Solo administradores
     */
    public function scopeAdministradores($query)
    {
        return $query->where('rol', 'administrador');
    }

    /**
     * Scope: Solo vendedores
     */
    public function scopeVendedores($query)
    {
        return $query->where('rol', 'vendedor');
    }

    // ===================================
    // 🔗 RELACIONES
    // ===================================

    /**
     * Relación: Un usuario tiene un cliente
     */
    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'user_id', 'id');
    }
}