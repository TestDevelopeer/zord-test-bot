<?php

namespace App\Models;

use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Platform\Models\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'telegram_chat_id',
        'telegram_clicks',
        'telegram_status',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions'          => 'array',
        'email_verified_at'    => 'datetime',
        'telegram_clicks'      => 'integer',
        'telegram_chat_id'     => 'integer',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
           'id'              => Where::class,
           'name'            => Like::class,
           'email'           => Like::class,
           'telegram_chat_id' => Where::class,
           'telegram_status' => Like::class,
           'telegram_clicks' => Where::class,
           'updated_at'      => WhereDateStartEnd::class,
           'created_at'      => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'telegram_chat_id',
        'telegram_status',
        'telegram_clicks',
        'updated_at',
        'created_at',
    ];
}
