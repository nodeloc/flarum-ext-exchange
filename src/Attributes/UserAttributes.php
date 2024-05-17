<?php

namespace Nodeloc\Exchange\Attributes;

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\User\User;

class UserAttributes
{
    public function __invoke(BasicUserSerializer $serializer, User $user): array
    {
        if ($serializer->getActor()->cannot('queryOthersExchange', $user)) {
            return [];
        }

        return [
            'canQueryOthersExchange' => true,
        ];
    }
}
