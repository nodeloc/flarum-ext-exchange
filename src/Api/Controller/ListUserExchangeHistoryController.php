<?php

namespace Nodeloc\Exchange\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\User\UserRepository;
use Nodeloc\Exchange\Api\Serializer\ExchangeHistorySerializer;
use Nodeloc\Exchange\model\UserExchangeHistory;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Support\Arr;
use Tobscure\JsonApi\Document;
use Flarum\Http\UrlGenerator;

class ListUserExchangeHistoryController extends AbstractListController
{
    protected $url;
    public $serializer = ExchangeHistorySerializer::class;

    public $include = [
        'user',
        'createUser'
    ];

    protected $repository;

    public function __construct(UserRepository $repository, UrlGenerator $url)
    {
        $this->url = $url;
        $this->repository = $repository;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $params = $request->getQueryParams();
        $actor = $request->getAttribute('actor');
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $userId = Arr::get($request->getQueryParams(), 'id');
        if (!$userId) {
            $userId = $actor->id;
        }
        $ExchangeQuery = UserExchangeHistory::query()->where(["user_id"=>$userId]);
        $ExchangeResult = $ExchangeQuery
            ->skip($offset)
            ->take($limit + 1)
            ->orderBy('id', 'desc')
            ->get();

        $hasMoreResults = $limit > 0 && $ExchangeResult->count() > $limit;

        if($hasMoreResults){
            $ExchangeResult->pop();
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('user.exchange.history', ['id' => $userId]),
            $params,
            $offset,
            $limit,
            $hasMoreResults ? null : 0
        );

        return $ExchangeResult;
    }
}
