<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['name', 'type', 'address', 'is_active'];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
