<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $table = 'section';
    protected $primaryKey = 'id_section';
    protected $guarded = [];

    public function produk()
    {
        return $this->hasMany(Produk::class, 'id_section', 'id_section');
    }
}
