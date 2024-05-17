<?php

namespace Nodeloc\Exchange\Validator;

use Flarum\Foundation\AbstractValidator;

class ExchangeValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        // See https://laravel.com/docs/8.x/validation#available-validation-rules for more information.
        'credits' => 'required',
    ];
}
