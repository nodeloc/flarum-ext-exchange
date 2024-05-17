<?php

/*
 * This file is part of Nodeloc/flarum-ext-exchange.
 *
 * Copyright (c) 2023 Nodeloc.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Api\Serializer\BasicUserSerializer;

use Nodeloc\Exchange\Api\Controller\ListUserExchangeHistoryController;
use Nodeloc\Exchange\Api\Controller\CreateExchangeRecordController;
use Nodeloc\Exchange\Attributes\UserAttributes;
use Nodeloc\Exchange\Event\MoneyAllHistoryEvent;
use Nodeloc\Exchange\Listeners\MoneyAllHistoryListeners;
use Nodeloc\Exchange\Listeners\ExchangeListeners;
use Nodeloc\Exchange\Event\ExchangeEvent;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/less/forum.less')
        ->route('/u/{username}/exchange', 'nodeloc-exchange.forum.nav'),
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/less/admin.less'),
    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\ApiSerializer(BasicUserSerializer::class))
        ->attributes(UserAttributes::class),
    (new Extend\Settings())
        ->serializeToForum('exchange_rate', 'nodeloc-exchange.exchange_rate', 'intval', 0)
        ->serializeToForum('api_url', 'nnodeloc-exchange.api_url',)
        ->serializeToForum('api_token', 'nodeloc-exchange.api_token',),

    (new Extend\Routes('api'))
        ->get('/users/{id}/exchange', 'user.exchange.history', ListUserExchangeHistoryController::class)
        ->post('/exchange', 'nodeloc.exchange.create', CreateExchangeRecordController::class),
];
