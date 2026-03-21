<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Customer extends Model
{
    use Searchable;

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

    public function searchableAs(): string
    {
        return 'customers';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'representative_name' => $this->representative_name,
            'representative_phone' => $this->representative_phone,
            'license_count' => $this->license_count,
            'notes' => $this->notes,
        ];
    }
}
