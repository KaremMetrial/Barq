<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

class AdminNotification extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    protected $fillable = [
        'data',
        'target_type',
        'target_data',
        'top_users_count',
        'performance_metric',
        'scheduled_at',
        'sent_at',
        'total_sent',
        'total_failed',
        'total_delivered',
        'admin_id',
        'is_scheduled',
        'is_sent',
    ];

    public $translatedAttributes = [
        'title',
        'body',
    ];

    protected $casts = [
        'data' => 'array',
        'target_data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'is_sent' => 'boolean',
    ];

    /**
     * Get the admin who created this notification.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(\Modules\Admin\Models\Admin::class);
    }

    /**
     * Scope to get scheduled notifications that are ready to be sent.
     */
    public function scopeReadyToSchedule($query)
    {
        return $query->where('is_scheduled', true)
            ->where('is_sent', false)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope to get notifications by target type.
     */
    public function scopeByTargetType($query, $targetType)
    {
        return $query->where('target_type', $targetType);
    }

    /**
     * Scope to get notifications by admin.
     */
    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Get the notification statistics.
     */
    public function getStatisticsAttribute()
    {
        $totalAttempts = $this->total_sent + $this->total_failed;
        $successRate = $totalAttempts > 0 ? round(($this->total_delivered / $totalAttempts) * 100, 2) : 0;

        return [
            'total_sent' => $this->total_sent,
            'total_failed' => $this->total_failed,
            'total_delivered' => $this->total_delivered,
            'total_attempts' => $totalAttempts,
            'success_rate' => $successRate . '%',
        ];
    }
}
