<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    use HasUuid, HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'conditions',
        'actions',
        'operator',
        'forwards',
        'replies',
        'sends',
        'active',
        'order'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'active' => 'boolean',
        'forwards' => 'boolean',
        'replies' => 'boolean',
        'sends' => 'boolean',
        'conditions' => 'array',
        'actions' => 'array'
    ];

    /**
     * Get the user for the rule.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    public function activate()
    {
        $this->update(['active' => true]);
    }
}
