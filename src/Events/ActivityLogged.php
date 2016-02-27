<?php

namespace Clumsy\Loggerhead\Events;

use Clumsy\Loggerhead\Models\Activity;

class ActivityLogged
{
    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }
}
