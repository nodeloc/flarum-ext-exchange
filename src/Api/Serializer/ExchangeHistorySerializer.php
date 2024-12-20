<?php

/*
 * This file is part of askvortsov/flarum-moderator-warnings
 *
 *  Copyright (c) 2021 Alexander Skvortsov.
 *
 *  For detailed copyright and license information, please view the
 *  LICENSE file that was distributed with this source code.
 */

namespace Nodeloc\Exchange\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Nodeloc\Exchange\model\UserExchange;

class ExchangeHistorySerializer extends AbstractSerializer
{
    protected $type = 'userExchangeHistory';

    protected function getDefaultAttributes($data){
        $attributes = [
            'id' => $data->id,
            'money' => $data->money,
            'type' => $data->type,
            'tx_hash' => $data->tx_hash,
            'created_at' => date("Y-m-d H:i:s", strtotime($data->created_at))
        ];

        return $attributes;
    }

    protected function User($Exchange){
        return $this->hasOne($Exchange, BasicUserSerializer::class);
    }

    protected function createUser($Exchange){
        return $this->hasOne($Exchange, BasicUserSerializer::class);
    }
}
