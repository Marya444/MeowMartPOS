<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'cost_price',
        'stock_quantity',
        'min_stock_level',
        'barcode',
        'category',
        'image_path'
    ];
}
