<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "clients";
    protected $fillable = [
        'user_id',
        'password',
        'login',
        'role_id'
    ];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    public function role(){
        return $this->belongsTo(Role::class,'role_id');
    }


}
