<?php

namespace Nodeloc\Exchange\Repository;

use Illuminate\Contracts\Container\Container;
use Flarum\Foundation\Paths;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\DB;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Nodeloc\Exchange\Model\UserExchangeHistory;
use Nodeloc\Exchange\Validator\ExchangeValidator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\UrlGenerator;
use Illuminate\Mail\Message;
use Illuminate\Contracts\Events\Dispatcher;
use Mattoid\MoneyHistory\Event\MoneyHistoryEvent;

class ExchangeRepository
{

    /**
     * @var ExchangeValidator
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var UrlGenerator
     */
    protected $url;

    private $email;

    private $api_token;

    private $api_url;
    /**
     * @var int 注册用户默认用户组
     */
    protected $defaultGroupId = 3;
    private $exchange_rate;
    protected $events;

    public function __construct(
        ExchangeValidator           $validator,
        SettingsRepositoryInterface $settings,
        TranslatorInterface         $translator,
        ExtensionManager            $extensions,
        UrlGenerator                $url,
        Dispatcher                  $events,

    )
    {
        $this->validator = $validator;
        $this->settings = $settings;
        $this->translator = $translator;
        $this->extensions = $extensions;
        $this->url = $url;
        $this->exchange_rate = (int)$this->settings->get('nodeloc-exchange.exchange_rate', 10);
        $this->events = $events;

    }

    /**
     * @return Builder
     */
    public function query()
    {
        return UserExchangeHistory::query();
    }

    /**
     * @param int $id
     * @param User $actor
     * @return Exchange
     */
    public function findOrFail($id, User $actor = null): Exchange
    {
        return UserExchangeHistory::findOrFail($id);
    }

    /**
     * 检查购买余额限制
     *
     * @param int $money
     * @return bool
     * @throws PermissionDeniedException
     */
    private function checkBuyMoney(int $money, int $credits): bool
    {
        if ($credits < 1) {
            throw new PermissionDeniedException('兑换积分数量不正确!');
        }
        // 试图扣钱
        if (($money - $this->exchange_rate * $credits) < 0) {
            throw new PermissionDeniedException('能量不足!');
        }
        return true;
    }
    private function requestExchange($credits)
    {
        // Step 1: 查询用户信息
        $userInfoUrl = $this->api_url . '/api/admin/client/get';
        $queryParams = [
            'email' => $this->email,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $userInfoUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "{$this->admin}:{$this->api_token}", // Basic Auth
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_HTTPGET => true,
            CURLOPT_POSTFIELDS => http_build_query($queryParams),
        ]);

        $userResponse = curl_exec($curl);

        if (empty($userResponse)) {
            throw new \RuntimeException('无法查询用户信息，请联系管理组');
        }

        $userResult = json_decode($userResponse, true);

        // 检查查询是否成功
        if (isset($userResult['error']) && $userResult['error']) {
            throw new \RuntimeException("用户查询失败: {$userResult['error']['message']}");
        }

        $userId = $userResult['result']['id'] ?? null;

        if (empty($userId)) {
            throw new \RuntimeException('未找到用户，请检查邮箱地址是否正确');
        }

        // Step 2: 为用户充值
        $url = $this->api_url . '/api/admin/client/balance_add_funds';

        $params = [
            'id' => $userId,
            'amount' => $credits,
            'description' => "Convert credits for user {$this->email}",
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => "{$this->admin}:{$this->api_token}", // Basic Auth
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
        ]);

        $response = curl_exec($curl);

        if (empty($response)) {
            throw new \RuntimeException('积分转换出错啦~请联系管理组');
        }

        curl_close($curl);

        $result = json_decode($response, true);

        if (isset($result['error']) && $result['error']) {
            throw new \RuntimeException("充值失败: {$result['error']['message']}");
        }

        return $result;
    }

    /**
     * 创建邀请码
     *
     * @param User $actor 用户对象
     * @param array $data 请求参数
     * @return Exchange
     * @throws ValidationException|PermissionDeniedException
     * @throws \Exception
     */
    public function store(User $actor, array $data): UserExchangeHistory
    {
        $this->validator->assertValid($data);
        $this->email = $actor->getAttribute('email');
        $this->api_url = $this->settings->get('nodeloc-exchange.api_url');
        $this->api_token = $this->settings->get('nodeloc-exchange.api_token');
        $credits = (int) $data['credits'];

        // 锁住用户行再读余额：否则并发请求会同时通过 checkBuyMoney，
        // 每一笔都换到积分，余额却只扣一次。
        return DB::transaction(function () use ($actor, $credits) {
            $locked = User::query()->lockForUpdate()->findOrFail($actor->id);
            $this->checkBuyMoney((int) $locked->money, $credits);

            // 发送远程请求
            $responseData = $this->requestExchange($credits);
            $success = $responseData['success'] ?? false;
            $message = $responseData['message'] ?? '';

            if (!$success) {
                throw new \RuntimeException($message);
            }

            // 扣减金额
            $locked->money -= $this->exchange_rate * $credits;
            $source = 'EXCHANGE';
            $sourceDesc = '能量转换为积分';

            $this->events->dispatch(new MoneyHistoryEvent($locked, -$this->exchange_rate * $credits, $source, $sourceDesc));
            $locked->save();

            return UserExchangeHistory::create([
                'user_id' => $locked->id,
                'money' => $this->exchange_rate * $credits,
                'credits' => $credits,
            ]);
        });
    }
}
