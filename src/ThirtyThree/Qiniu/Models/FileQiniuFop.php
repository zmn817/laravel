<?php

namespace ThirtyThree\Qiniu\Models;

use Illuminate\Database\Eloquent\Model;

class FileQiniuFop extends Model
{
    protected $table = 'file_qiniu_fop';

    protected $fillable = [
        'fop_id',
        'bucket_name',
        'bucket',
        'key',
        'usage',
        'save_info',
        'context',
        'pipeline',
        'code',
        'desc',
        'request_id',
        'items',
    ];

    protected $casts = ['save_info' => 'json', 'context' => 'json', 'items' => 'json'];
}
