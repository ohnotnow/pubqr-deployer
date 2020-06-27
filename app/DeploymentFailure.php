<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeploymentFailure extends Model
{
    protected $fillable = [
        'uuid',
        'message',
        'stack_trace',
    ];
}
