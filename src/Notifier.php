<?php

namespace Clumsy\Loggerhead;

use Illuminate\Support\Collection;
use Clumsy\Loggerhead\Models\Activity;
use Clumsy\Loggerhead\Models\Notification;
use Clumsy\Loggerhead\Models\NotificationMeta;
use Carbon\Carbon;

class Notifier
{
    protected function create($notificationArray, $visibleFrom = null)
    {
        $notificationArray = (array)$notificationArray;

        $attributes = [];
        array_walk($notificationArray, function ($value, $key) use (&$attributes) {

            // Allow $notificationArray to be an associative array of slug => meta
            // or just a simple array with the slug as its sole value

            if (!$key) {
                $attributes = [
                    $value => []
                ];
            } else {
                $attributes[$key] = $value;
            }
        });

        $activity = app('clumsy.loggerhead')->log(key($attributes), head($attributes));

        return $activity->createNotification($visibleFrom);
    }

    public function batchOrSingle(Notification $notification, $target)
    {
        if (!($target instanceof Collection)) {
            $target = collect([$target]);
        }

        return $this->batch($notification, $target);
    }

    public function batch(Notification $notification, Collection $items)
    {
        foreach ($items as $item) {
            $item->dispatchNotification($notification);
        }
    }

    public function notify($attributes = [], $target = null, $visibleFrom = null)
    {
        if (!$target) {
            return false;
        }

        return $this->batchOrSingle($this->create($attributes, $visibleFrom), $target);
    }

    public function dissociate($associationType, $associationId, $options = [])
    {
        $defaults = [
            'triggered'  => false,
            'slug'       => false,
            'meta_key'   => false,
            'meta_value' => false,
        ];

        $options = array_merge($defaults, $options);

        $query = DB::table('clumsy_notification_associations')
                   ->select('clumsy_notification_associations.id')
                   ->join('clumsy_notifications', 'clumsy_notifications.id', '=', 'clumsy_notification_associations.notification_id')
                   ->join('clumsy_activity_meta', 'clumsy_notifications.activity_id', '=', 'clumsy_activity_meta.activity_id')
                   ->where('association_type', $associationType)
                   ->where('association_id', $associationId)
                   ->where('triggered', $options['triggered']);

        if ($options['slug']) {
            $query->where('slug', $options['slug']);
        }

        if ($options['meta_key']) {
            $query->where('key', $options['meta_key']);
        }

        if ($options['meta_value']) {
            $query->where('value', $options['meta_value']);
        }

        $notifications = $query->pluck('id');

        if (count($notifications)) {
            return DB::table('clumsy_notification_associations')->whereIn('id', $notifications)->delete();
        }

        return false;
    }
}
