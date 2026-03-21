<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'representative_name',
        'representative_phone',
        'license_count',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_count' => 'integer',
        ];
    }
}
