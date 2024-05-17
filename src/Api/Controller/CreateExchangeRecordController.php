<?php


namespace Nodeloc\Exchange\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ErrorHandling\HandledError;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Bus\Dispatcher;
use Nodeloc\Exchange\Api\Serializer\ExchangeHistorySerializer;
use Nodeloc\Exchange\Repository\ExchangeRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateExchangeRecordController extends AbstractCreateController
{

    public $serializer = ExchangeHistorySerializer::class;

    /**
     * @var Dispatcher
     */
    protected $bus;

    protected $repository;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus, ExchangeRepository $repository)
    {
        $this->bus = $bus;
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $data = $request->getParsedBody();

        // 调用邀请码创建
        try {
            return $this->repository->store($actor, $data);
        } catch (\Exception|PermissionDeniedException $e) {
            header('Content-Type:  application/json; charset=UTF-8');
            die(json_encode([
                'error' => $e->getMessage(),
            ]));
        }

    }
}
