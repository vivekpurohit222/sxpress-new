<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasRoles;
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
        // The "hashed" cast hashes the value on assignment exactly once.
        // It is idempotent: Hash::isHashed() guards against re-hashing an
        // already-hashed value, so callers may pass plaintext (controllers)
        // or a pre-hashed string (seeders) safely. This replaces the old
        // setPasswordAttribute() mutator, which double-hashed seeded values.
        'password' => 'hashed',
    ];

    public function officeall(){
        $id = Auth::user()->id;
        return $data = DB::table('users')
            ->where('id',$id)
            ->pluck('office');
    }
}