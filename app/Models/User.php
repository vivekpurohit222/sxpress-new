<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'office',
        'phone',
        'is_active',
        'branch_id',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Get the branch this user belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class ?? \stdClass::class, 'branch_id');
    }

    /**
     * Scope: only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: only inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
}
