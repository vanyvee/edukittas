<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
/**
 * Platform-level super admin — separate from school staff (User) and
 * not scoped to any school. tinyIncrements caps this table at 255
 * rows, which fits an internal admin table but is worth remembering
 * if that assumption ever changes.
 */
class Admin extends Authenticatable
{
      use  Notifiable;
      
    public $timestamps = false;

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone',
        'recovery_phone',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
    ];
}