<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "roles";
    protected $fillable = [
        'name'
    ];

    public function client(){
        return $this->hasMany(Client::class,'role_id');
    }


}
