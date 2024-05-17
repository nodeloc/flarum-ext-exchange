<?php

namespace Nodeloc\Exchange\Model;

use Flarum\Database\AbstractModel;
use Flarum\Formatter\Formatter;
use Flarum\User\User;

class UserExchangeHistory extends AbstractModel
{
    protected $table = "user_exchange_history";
    public $timestamps = true;

    public $fillable = [
        'money',
        'credits',
        'user_id',

    ];

    /**
     * The text formatter instance.
     *
     * @var \Flarum\Formatter\Formatter
     */
    protected static $formatter;

    /**
     * Get the text formatter instance.
     *
     * @return \Flarum\Formatter\Formatter
     */
    public static function getFormatter()
    {
        return static::$formatter;
    }

    /**
     * Set the text formatter instance.
     *
     * @param \Flarum\Formatter\Formatter $formatter
     */
    public static function setFormatter(Formatter $formatter)
    {
        static::$formatter = $formatter;
    }

    public function User(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function createUser(){
        return $this->hasOne(User::class, 'id', 'create_user_id');
    }
}
