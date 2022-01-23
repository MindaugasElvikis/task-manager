<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property User owner
 * @property Collection|User[] attached_users
 */
class Task extends Model
{
    use HasFactory;

    public const TYPE_BASIC = 'basic';
    public const TYPE_ADVANCED = 'advanced';
    public const TYPE_EXPERT = 'expert';

    public const TYPES = [
        self::TYPE_BASIC,
        self::TYPE_ADVANCED,
        self::TYPE_EXPERT,
    ];

    public const STATUS_TODO = 'todo';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_HOLD = 'hold';

    public const STATUSES = [
        self::STATUS_TODO,
        self::STATUS_CLOSED,
        self::STATUS_HOLD,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attached_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
