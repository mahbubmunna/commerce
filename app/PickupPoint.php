<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    public function staff(){
    	return $this->belongsTo(Staff::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
