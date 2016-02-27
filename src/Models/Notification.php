<?php

namespace Clumsy\Loggerhead\Models;

use Carbon\Carbon;
use Clumsy\Loggerhead\Models\Activity;
use Clumsy\Loggerhead\Models\ActivityMeta;
use Clumsy\Loggerhead\Models\Traits\Resolvable;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Mail;

class Notification extends Eloquent
{
    use Resolvable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'clumsy_notifications';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function getDates()
    {
        return [
            'visible_from',
        ];
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    protected function getMetaRelationship()
    {
        return $this->activity->meta();
    }

    public function shouldTrigger()
    {
        return Carbon::now()->diffInSeconds($this->visible_from, false) <= 0;
    }

    public function mail(array $recipients)
    {
        $subject = $this->title ? $this->title : app('clumsy.loggerhead')->resolveTitle($this);

        $view = view()->exists("clumsy/loggerhead::emails.{$this->slug}")
                ? "clumsy/loggerhead::emails.{$this->slug}"
                : "clumsy/loggerhead::email";

        Mail::send($view, ['notification' => $this], function ($message) use ($recipients, $subject) {

            foreach ($recipients as $address => $recipient) {
                if (!$address) {
                    // Allow recipients to be non-associative array of addresses
                    $address = $recipient;
                }

                $message->to($address, $recipient)->subject($subject);
            }
        });
    }

    public function getTitleAttribute()
    {
        if (!array_key_exists('title', $this->getAttributes())) {
            $this->resolve();
        }

        return array_get($this->getAttributes(), 'title');
    }
}
