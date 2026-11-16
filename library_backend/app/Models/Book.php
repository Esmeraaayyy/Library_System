<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'isbn',
        'title',
        'author',
        'category',
        'total_copies',
        'available_copies',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}