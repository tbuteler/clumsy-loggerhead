<?php

namespace Clumsy\Loggerhead\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Clumsy\Loggerhead\Models\ActivityMeta;
use Clumsy\Loggerhead\Models\Notification;
use Clumsy\Loggerhead\Models\Traits\Resolvable;
use Carbon\Carbon;

class Activity extends Eloquent
{
    use Resolvable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'clumsy_activities';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function meta()
    {
        return $this->hasMany(ActivityMeta::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    protected function getMetaRelationship()
    {
        return $this->meta();
    }

    protected function hasJoinedMeta($query)
    {
        return isset($query->loggerheadMetaJoined) && $query->loggerheadMetaJoined;
    }

    public function scopeJoinMeta($query)
    {
        $query->join('clumsy_activity_meta', 'clumsy_activities.id', '=', 'clumsy_activity_meta.activity_id');
        $query->loggerheadMetaJoined = true;
    }

    public function scopeJoinMetaIfNotJoined($query)
    {
        if (!$this->hasJoinedMeta($query)) {
            $query->joinMeta();
        }
    }

    public function scopeMetaKeyValue($query, $key, $value)
    {
        $query->joinMetaIfNotJoined()
              ->where(function ($q) use ($key, $value) {
                $q->where('clumsy_activity_meta.key', $key)
                  ->where('clumsy_activity_meta.value', $value);
              });
    }

    public function scopeOrMetaKeyValue($query, $key, $value)
    {
        $query->joinMetaIfNotJoined()
              ->orWhere(function ($q) use ($key, $value) {
                $q->where('clumsy_activity_meta.key', $key)
                  ->where('clumsy_activity_meta.value', $value);
              });
    }

    public function createNotification(Carbon $visibleFrom = null, $slugOverride = null)
    {
        if (is_null($visibleFrom)) {
            $visibleFrom = Carbon::now();
        }

        return $this->notifications()->create([
            'slug'         => $slugOverride ?: $this->slug,
            'visible_from' => $visibleFrom->toDateTimeString(),
        ]);
    }

    public function notify($targets, $visibleFrom = null)
    {
        app('clumsy.loggerhead')->notifier()->batchOrSingle($this->createNotification($visibleFrom), $targets);

        return $this;
    }

    public function notifyMultipleTimes($targets, array $notificationsArray = [])
    {
        foreach ($notificationsArray as $slug => $visibleFrom) {
            app('clumsy.loggerhead')->notifier()->batchOrSingle($this->createNotification($visibleFrom, $slug), $targets);
        }

        return $this;
    }
}
