<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'responsible_person', 'contact_email'];

    // Um departamento possui muitas licenças
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
