<?php
// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;
    
    protected $table = 'roles';
    protected $fillable = ['name'];
    public $timestamps = false;
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'role_id');
    }
}