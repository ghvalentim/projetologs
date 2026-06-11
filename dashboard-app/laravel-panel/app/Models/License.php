<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id', 'software_name', 'license_key', 
        'supplier', 'total_slots', 'used_slots', 
        'purchased_at', 'expires_at', 'notes'
    ];

    // Avisa o Laravel que esses campos devem ser tratados como objetos de data (Carbon)
    protected $casts = [
        'purchased_at' => 'date',
        'expires_at' => 'date',
    ];

    // Uma licença pertence a um departamento específico
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}