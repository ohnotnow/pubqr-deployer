<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_FAILED = 'failed';
    const STATUS_SUCCEEDED = 'succeeded';

    protected $fillable = [
        'email',
        'api_key',
        'url',
        'shop_name',
        'uuid',
        'status',
    ];

    public function markFailed()
    {
        $this->update(['status' => Deployment::STATUS_FAILED]);
    }

    public function markSucceeded()
    {
        $this->update(['status' => Deployment::STATUS_SUCCEEDED]);
    }
}
