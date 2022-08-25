<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ToDo extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_NOT_DONE = 'not-done';
    const STATUS_DONE = 'done';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'task_id', 'to_do', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function mentions()
    {
        return $this->hasMany(ToDoMention::class);
    }

    public function mentionUsers()
    {
        return $this->belongsToMany(User::class, ToDoMention::class, 'to_do_id', 'user_id');
    }
}
