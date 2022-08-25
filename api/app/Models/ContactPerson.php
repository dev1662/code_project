<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactPerson extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
     const TYPE_DELETE = 'delete';
    const TYPE_DONT_DELETE = 'dont_delete';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'type','status',
    ];

    public function phones()
    {
        return $this->hasMany(ContactPersonPhone::class);
    }

    public function emails()
    {
        return $this->hasMany(ContactPersonEmail::class);
    }
    public function task()
    {
        return $this->hasMany(Task::class);
    }
}
