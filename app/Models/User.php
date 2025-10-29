<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'default_role',
        'status',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function fileMovements()
    {
        return $this->hasMany(FileMovement::class, 'sender_id', 'recipient_id');
    }

    public function userDetail()
    {
        return $this->hasOne(UserDetails::class);
    }
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }
    public function tenant_department()
    {
        return $this->belongsTo(TenantDepartment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'customerId');
    }
    // public function staffProfile()
    // {
    //     return $this->hasOne(staffProfile::class);
    // }
}
