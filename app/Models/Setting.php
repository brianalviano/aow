<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'site_name',
        'logo',
        'contact_email',
        'whatsapp_number',
        'address',
        'latitude',
        'longitude',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
    ];
}
