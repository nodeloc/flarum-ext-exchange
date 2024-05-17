<?php

namespace Nodeloc\Exchange\Repository;

use Illuminate\Contracts\Container\Container;
use Flarum\Foundation\Paths;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Mail\Mailer;
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

    /**
     * 生成一个 key
     *
     * @param int $user_id
     * @return string
     * @throws \Exception
     */
    private function requestExchange($credits): string
    {
        // 设置API端点URL
        $url = $this->api_url.'/api/admin/v1/users/' . $this->email . '/' . $credits;

        // 设置请求头部，包含Bearer Token
        $headers = [
            'Authorization: Bearer ' . $this->api_token, // 注意这里的 Bearer 关键字
            'Accept: application/json',
        ];

        // 初始化cURL
        $curl = curl_init();
        // 设置cURL选项
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true, // 设置为 POST 请求
        ]);

        // 执行请求并获取响应
        $response = curl_exec($curl);

        // 检查响应是否为空
        if (empty($response)) {
            // 响应为空，返回一个默认值或者抛出异常
            throw new \RuntimeException('积分转换出错啦~请联系管理组');
        }

        // 关闭cURL
        curl_close($curl);

        // 解析JSON响应
        $responseData = json_decode($response, true);
        return $responseData;
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
        $credits = $data['credits'];
        // 检查用户余额是否符合要求
        $money = $actor->getAttribute('money');
        $this->checkBuyMoney($money, $credits);
        // 发送远程请求
        $responseData = $this->requestExchange($credits);
        // 提取success和message字段
        $success = $responseData['success'] ?? false;
        $message = $responseData['message'] ?? '';

        // 进行下一步操作，例如根据success和message做相应处理
        if ($success) {
            // 扣减金额
            $actor->money -= $this->exchange_rate * $credits;
            $source = 'EXCHANGE';
            $sourceDesc = '能量转换为积分';

            $this->events->dispatch(new MoneyHistoryEvent($actor, -$this->exchange_rate * $credits, $source, $sourceDesc));
            $actor->save();
            // 创建邀请码
            $record = UserExchangeHistory::create([
                'user_id' => $actor->id,
                'money' => $this->exchange_rate * $credits,
                'credits' => $credits,
            ]);

            return $record;
        } else {
            throw new \RuntimeException($message);
        }


    }
}
