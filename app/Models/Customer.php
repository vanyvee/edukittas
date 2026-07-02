<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['username','phone','password','address','sex','surname', 'first_name'];
    protected $hidden = ['password'];

    protected $cast = [ 'status'=>'boolean',];

    public function isGuest(): bool
    {
        return !$this->status;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

}
