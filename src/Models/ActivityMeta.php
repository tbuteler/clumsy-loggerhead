<?php

namespace Clumsy\Loggerhead\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class ActivityMeta extends Eloquent
{
    protected $guarded = ['id'];

    protected $table = 'clumsy_activity_meta';

    public $timestamps = false;
}
