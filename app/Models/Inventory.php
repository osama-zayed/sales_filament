<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'location',
        'description',
    ];
    public function supplies()
    {
        return $this->hasMany(Supply::class);
    }
    public function expenses()
    {
        return $this->hasMany(Exchange::class);
    }
}