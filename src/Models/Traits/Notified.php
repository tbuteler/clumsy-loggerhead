<?php

namespace Clumsy\Loggerhead\Models\Traits;

use Illuminate\Support\Facades\DB;
use Clumsy\Loggerhead\Models\Notification;
use Carbon\Carbon;

trait Notified
{
    public function notifier()
    {
        return $this->morphToMany(Notification::class, 'association', 'clumsy_notification_associations');
    }

    public function notifications()
    {
        return $this->notifier()
                    ->with('activity', 'activity.meta')
                    ->withPivot('read', 'triggered')
                    ->where('visible_from', '<=', Carbon::now()->toDateTimeString())
                    ->orderBy('visible_from', 'desc');
    }

    public function allNotifications()
    {
        return $this->notifications()->get();
    }

    public function readNotifications()
    {
        return $this->notifications()->read()->get();
    }

    public function unreadNotifications()
    {
        return $this->notifications()->unread()->get();
    }

    public function notificationMailRecipients(Notification $notification)
    {
        return [];
    }

    public function updateReadStatus($read = true, $notificationId = false)
    {
        $query = DB::table('clumsy_notification_associations');

        if ($notificationId) {
            $query->where('notification_id', $notificationId);
        }

        return $query->where('association_type', get_class($this))
                     ->where('association_id', $this->id)
                     ->update(['read' => (int)$read]);
    }

    public function markAllNotificationsAsRead()
    {
        return $this->updateReadStatus(true);
    }

    public function markNotificationAsRead($notificationId)
    {
        return $this->updateReadStatus(true, $notificationId);
    }

    public function markNotificationAsUnread($notificationId)
    {
        return $this->updateReadStatus(false, $notificationId);
    }

    public function dispatchNotification(Notification $notification)
    {
        $trigger = $notification->shouldTrigger();

        $this->notifier()->attach($notification->id, ['triggered' => $trigger]);

        if ($trigger) {
            $this->triggerNotification($notification);
        }
    }

    public function triggerNotification(Notification $notification)
    {
        $recipients = (array)$this->notificationMailRecipients($notification);

        if (count(array_filter($recipients))) {
            $notification->mail(array_filter($recipients));
        }
    }

    public function triggerNotificationAndSave(Notification $notification)
    {
        $this->triggerNotification($notification);

        DB::table('clumsy_notification_associations')
          ->where('notification_id', $notification->id)
          ->where('association_type', get_class($this))
          ->where('association_id', $this->id)
          ->update(['triggered' => true]);
    }

    public function notify($attributes = [], $visibleFrom = null)
    {
        app('clumsy.loggerhead')->notifier()->notify($attributes, $this, $visibleFrom);

        return $this;
    }

    public function removeNotifications($options = [])
    {
        app('clumsy.loggerhead')->notifier()->dissociate(get_class($this), $this->getKey(), $options);

        return $this;
    }
}
