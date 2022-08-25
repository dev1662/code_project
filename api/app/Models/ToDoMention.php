<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ToDoMention extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'to_do_id', 'user_id',
    ];

    public function toDo()
    {
        return $this->belongsTo(ToDo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
