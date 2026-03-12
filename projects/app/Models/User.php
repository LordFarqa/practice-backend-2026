<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $table = "users";
    protected $fillable = [
        'name',
        'surname',
        'last_name',
        'email',
        'phone_number'
    ];
    protected $hidden = [
        'updated_at',
        'id',
        'created_at'
    ];
    

    public function client(){
        return $this->hasOne(Client::class,'user_id');
    }
    public function booking(){
        return $this->hasMany(BookingRooms::class,'user_id');
    }


}
