<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class CentralOnboarding extends Model
{
    use HasFactory, SoftDeletes,Notifiable;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';

    public $table = 'onboardings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'otp',
        'organization_name',
        'subdomain',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
