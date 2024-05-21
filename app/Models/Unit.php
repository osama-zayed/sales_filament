<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    protected $table = 'units';
    protected $fillable = ['id', 'unit_name'];
    public function Products()
    {
        return $this->belongsToMany(Product::class, 'product_units', 'unit_id', 'product_id')
               ->withPivot('product_price')
               ->withTimestamps();
    }
}
