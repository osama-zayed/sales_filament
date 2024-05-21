<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supply extends Model
{
    use HasFactory;
    protected $table = 'supplies';
    protected $fillable = [
        'id',
        'invoice_number',
        'supply_date',
        'supplier_name',
        'total_amount',
        'notes',
    ];
   
}
