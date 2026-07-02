<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;


    protected $fillable  = ['username', 'surname', 'firstName', 'email','phone' ,'address', 'day' , 'month', 'year'];
    protected $hidden = ['password'];
    
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role'=>'string',
            'status'=>'boolean'
        ];
    }

    public function isCeo(): bool {return $this->role === 'ceo';}
    public function isSupervisor(): bool {return $this->role ==='supervisor';}
    public function isCook(): bool {return $this->role === 'cook';}
    public function isCashier(): bool {return $this->role === 'cashier';}

    public function canViewAnalytics(): bool
    {
        return in_array($this-role, ['ceo','supervisor',]);
    }
    public function canManageProduct(): bool
    {
        return in_array($this->role, ['ceo','supervisor']);
    }

    public function canCookOrder(): bool
    {
        return $this->role === 'cook';
    }

    public function canProcessPayment(): bool
    {
        return in_array($this-role, ['ceo', 'cashier']);
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
