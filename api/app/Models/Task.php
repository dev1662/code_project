<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_LEAD = 'lead';
    const TYPE_TASK = 'task';

    const STATUS_OPEN = 'open';
    const STATUS_COMPLETED = 'completed';
    const STATUS_INVOICED = 'invoiced';
    const STATUS_CLOSED = 'closed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'client_id', 'contact_person_id', 'branch_id', 'category_id','user_id', 'type', 'subject', 'description', 'due_date', 'priority', 'status',
    ];

    // public function users()
    // {
    //     return $this->belongsToMany(User::class, TaskUser::class, 'task_id', 'user_id');
    // }
    // public function users()
    // {
    //     return $this->belongsToMany(CentralUser::class,'users','user_id');
    // }

    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contactPerson()
    {
        return $this->belongsTo(ContactPerson::class);
    }
}
