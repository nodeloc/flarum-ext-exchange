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

class WithDrawRepository
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

    private $blockchain_api_url;
    private $blockchain_token;
    private $blockchain_min;

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
        $this->blockchain_api_url = $this->settings->get('nodeloc-exchange.blockchain_api_url');
        $this->blockchain_token = $this->settings->get('nodeloc-exchange.blockchain_token');
        $this->blockchain_min = $this->settings->get('nodeloc-exchange.blockchain_min');

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
     * 检查购买余额限制
     *
     * @param int $money
     * @return bool
     * @throws PermissionDeniedException
     */
    private function checkBuyMoney(int $money, int $credits): bool
    {
        if ($credits < $this->blockchain_min) {
            throw new PermissionDeniedException('兑换数量最少需要'.$this->blockchain_min);
        }
        // 试图扣钱
        if (($money - $credits) < 0) {
            throw new PermissionDeniedException('能量不足!');
        }
        return true;
    }

    private function gene_signature(){
       // 获取当前时间（每 10 秒为一个周期）
       $timestamp = round(time() / 10);
       // 拼接签名字符串
       $signature_base = $this->blockchain_token . $timestamp;
       // 使用 SHA-512 算法生成签名
       $signature = hash('sha512', $signature_base);
       return $signature;
    }

    /**
     * 远程请求
     *
     * wallet_address: str: 要mint到的钱包地址
     * quantity:int:mint数量
     * signature:str:请求签名
     * @param int $user_id
     * @return string
     * @throws \Exception
     */
    private function requestWithDraw($withdraw_address,$credits): string
    {
        // 设置API端点URL
        $url = $this->blockchain_api_url.'/mint';

        // 生成请求签名
        $signature = $this->gene_signature();

        $queryParams = http_build_query([
            'wallet_address' => $withdraw_address,
            'quantity' => $credits,
            'signature' => $signature,
        ]);
        // 拼接完整URL
        $fullUrl = $url . '?' . $queryParams;
        // 设置请求头部
        $headers = [
            'Accept: application/json',
        ];
        // 初始化cURL
        $curl = curl_init();
        // 设置cURL选项
        curl_setopt_array($curl, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        // 执行请求并获取响应
        $response = curl_exec($curl);

        // 检查cURL错误
        if ($response === false) {
            throw new \RuntimeException('请求失败：' . curl_error($curl));
        }

        // 关闭cURL
        curl_close($curl);
        // 解析 JSON 响应
        $responseData = json_decode($response, true);

        // 检查解析结果
        if (!is_array($responseData)) {
            throw new \RuntimeException('服务器返回格式不正确');
        }

        // 检查返回 code 并处理结果
        if (isset($responseData['code']) && $responseData['code'] === 200) {
            // 成功返回交易哈希
            return $responseData['txhash'];
        }

        // 失败时返回错误信息
        $errorText = $responseData['text'] ?? '未知错误';
        throw new \RuntimeException('积分转换失败：' . $errorText);
    }

    /**
     * 提币
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
        $withdraw_address = $data['withdraw_address'];
        $credits = $data['credits'];
        // 检查用户余额是否符合要求
        $money = $actor->getAttribute('money');

        $this->checkBuyMoney($money,$credits);
        $tx_hash = $this->requestWithDraw($withdraw_address, $credits);
        $source = 'WITHDRAW';
        $sourceDesc = '能量提现';

        $this->events->dispatch(new MoneyHistoryEvent($actor, -$credits, $source, $sourceDesc));
        $actor->save();
        $record = UserExchangeHistory::create([
            'user_id' => $actor->id,
            'money' => $credits,
            'type' => 1,
            'tx_hash' => $tx_hash,
        ]);
        return $record;
    }
}
