<?php
// app/Models/Client.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory;
    
    protected $table = 'clients';
    protected $fillable = ['user_id', 'login', 'password', 'role_id'];
     
    public $timestamps = false;
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}