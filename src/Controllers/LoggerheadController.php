<?php

namespace Clumsy\Loggerhead\Controllers;

use Clumsy\Loggerhead\Contracts\NotifiedContract;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;

class LoggerheadController extends Controller
{
    protected function getModelFromRequest()
    {
        list($associationModel, $associationId) = explode('|', Crypt::decrypt(request('association')));
        return with(new $associationModel)->find($associationId);
    }

    protected function markAllNotificationsAsRead(NotifiedContract $association)
    {
        $association->markAllNotificationsAsRead();
    }

    protected function markNotificationAsRead(NotifiedContract $association, $notificationId)
    {
        $association->markNotificationAsRead($notificationId);
    }

    protected function markNotificationAsUnread(NotifiedContract $association, $notificationId)
    {
        $association->markNotificationAsUnread($notificationId);
    }

    public function readNotification()
    {
        if (request()->has('all_notifications') && request(('all_notifications')) {
            $this->markAllNotificationsAsRead($this->getModelFromRequest());
        } else {
            $this->markNotificationAsRead($this->getModelFromRequest(), request('notification_id'));
        }

        return response('ok');
    }

    public function unreadNotification()
    {
        $this->markNotificationAsUnread($this->getModelFromRequest(), request('notification_id'));

        return response('ok');
    }
}
