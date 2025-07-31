<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\HasUuids;

class User extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable, HasUuids;

  protected $table = 'users';
  protected $keyType = 'string';
  public $incrementing = false;

  protected $fillable = [
    'id',
    'name',
    'email',
    'password',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected $casts = [
    'email_verified_at' => 'datetime',
  ];
}
