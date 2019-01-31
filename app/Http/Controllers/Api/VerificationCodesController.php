<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        $phone = $request->input('phone');

        // 生成4位随机数，左侧补0
        $code = str_pad(random_int(1,9999), 4, 0, STR_PAD_LEFT);

        try {
            $result = $easySms->send($phone, [
                'content' => "{$code}为您的登录验证码，如非本人操作，请忽略本短信。"
            ]);
        } catch (NoGatewayAvailableException $exception) {
            $message = $exception->getException('qcloud')->getMessage();
            return $this->response->errorInternal($message ? : '短信发送异常');
        }

        $key = 'verificationCode_'.str_random(15);
        $expiredAt = now()->addMinutes(10);
        // 缓存验证码 10 分钟过期
        Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);

        return $this->response->array([
            'key' => $key,
            'expired_at' => $expiredAt,
        ])->setStatusCode(201);
    }
}
