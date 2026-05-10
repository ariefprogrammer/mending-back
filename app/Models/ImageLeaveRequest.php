<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageLeaveRequest extends Model
{
    protected $fillable = [
        'leave_request_id',
        'image_url',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }
}