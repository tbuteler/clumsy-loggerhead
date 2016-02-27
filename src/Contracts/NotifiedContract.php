<?php

namespace Clumsy\Loggerhead\Contracts;

use Clumsy\Loggerhead\Models\Notification;

interface NotifiedContract
{
    public function notifier();

    public function notifications();

    public function allNotifications();

    public function readNotifications();

    public function unreadNotifications();

    public function notificationMailRecipients(Notification $notification);

    public function updateReadStatus($read = true, $notificationId = false);

    public function markAllNotificationsAsRead();

    public function markNotificationAsRead($notificationId);

    public function markNotificationAsUnread($notificationId);

    public function dispatchNotification(Notification $notification);

    public function triggerNotification(Notification $notification);

    public function triggerNotificationAndSave(Notification $notification);

    public function notify($attributes = [], $visibleFrom = null);
}
