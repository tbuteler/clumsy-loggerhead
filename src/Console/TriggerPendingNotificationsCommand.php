<?php

namespace Clumsy\Loggerhead\Console;

use Carbon\Carbon;
use Clumsy\Loggerhead\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Check for un-triggered notifications and trigger them
 *
 * @author Tomas Buteler <tbuteler@gmail.com>
 */
class TriggerPendingNotificationsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'clumsy:trigger-pending-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for notifications not yet triggered and trigger them';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $pending = DB::table('clumsy_notification_associations')
                     ->join('clumsy_notifications', 'clumsy_notifications.id', '=', 'clumsy_notification_associations.notification_id')
                     ->where('triggered', false)
                     ->where('visible_from', '<=', Carbon::now()->toDateTimeString())
                     ->count();

        if (!$pending) {
            $this->info("No pending notifications to trigger");
        }

        Notification::with('meta')
                    ->select('*', 'clumsy_notification_associations.id as pivot_id')
                    ->join('clumsy_notification_associations', 'clumsy_notifications.id', '=', 'clumsy_notification_associations.notification_id')
                    ->where('triggered', false)
                    ->where('visible_from', '<=', Carbon::now()->toDateTimeString())
                    ->chunk(200, function (Collection $notifications) {

                        foreach ($notifications as $notification) {
                            $model = $notification->notification_association_type;
                            $target = $model::find($notification->notification_association_id);
                            if ($target) {
                                $target->triggerNotification($notification);
                            }
                        }

                        DB::table('clumsy_notification_associations')
                          ->whereIn('id', $notifications->pluck('pivot_id'))
                          ->update([
                            'triggered' => true,
                          ]);

                        $count = count($notifications);
                        $this->info("Triggered {$count} notifications");
                    });

        $this->info("All pending notifications triggered");
    }
}
