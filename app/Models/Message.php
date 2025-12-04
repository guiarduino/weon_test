<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    public const TABLE_NAME = 'messages';

    public const ID = 'id';
    public const PROVIDER_ID = 'provider_id';
    public const DIRECTION = 'direction';
    public const FROM = 'from';
    public const TO = 'to';
    public const TYPE = 'type';
    public const BODY = 'body';
    public const STATUS = 'status';
    public const ERROR_CODE = 'error_code';
    public const ERROR_REASON = 'error_reason';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const DELETED_AT = 'deleted_at';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'provider_id',
        'direction',
        'from',
        'to',
        'type',
        'body',
        'status',
        'error_code',
        'error_reason',
    ];

    protected $dates = ['deleted_at'];
}
