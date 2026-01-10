<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Ticket extends Model
{
    use HasFactory;

    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    protected $attributes = [
        'status' => self::STATUS_OPEN,
        'severity' => 3,
    ];

    protected $fillable = [
        'subject',
        'description',
        'category',
        'severity',
        'status',
        'created_by',
        'assigned_to',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'severity' => 'integer',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }
}
