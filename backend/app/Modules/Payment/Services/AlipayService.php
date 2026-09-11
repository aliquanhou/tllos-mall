<?php
namespace App\Modules\Payment\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class AlipayService extends PaymentService
{
    protected function getPayCode()
    {
        return 'alipay';
    }

    protected function checkSandboxMode()
    {
        if (empty($this->config)) return true;
        $required = ['app_id', 'merchant_private_key', 'alipay_public_key'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) return true;
        }
        return false;
    }

    /**
     * 支付宝配置是否完整
     */
    public function isConfigured()
    {
        if (empty($this->config)) return false;
        $required = ['app_id', 'merchant_private_key', 'alipay_public_key'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) return false;
        }
        return true;
    }

    /**
     * 检测是否为移动端
     */
    public function isMobile()
    {
        $userAgent = request()->header('User-Agent', '');
        if (empty($userAgent)) return false;
        return preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|iemobile|mmp|symbian|smartphone|midp|wap|phone|windows ce|pda|mobile|mini|palm|netfront)/i', $userAgent) > 0;
    }

    /**
     * 支付宝下单
     * PC端：电脑网站支付 alipay.trade.page.pay
     * 手机端：手机网站支付 alipay.trade.wap.pay（自动拉起支付宝APP）
     */
    public function unifiedOrder(array $params)
    {
        // 生产环境配置不完整：Fail-Closed
        if ($this->isProduction() && !$this->isConfigured()) {
            Log::warning('支付宝生产环境配置不完整，拒绝下单', [
                'out_trade_no' => $params['out_trade_no'] ?? '',
                'config_keys' => $this->config ? array_keys($this->config) : [],
            ]);
            return ['success' => false, 'message' => '支付宝支付暂未配置完成，请使用其他支付方式'];
        }

        if ($this->isSandbox) {
            Log::info('支付宝沙箱模式下单', $params);
            return $this->mockPayResult($params['out_trade_no'], $params['amount']);
        }

        try {
            $gateway = $this->config['gateway_url'] ?? 'https://openapi.alipay.com/gateway.do';
            $isMobile = $params['is_mobile'] ?? $this->isMobile();

            if ($isMobile) {
                $method = 'alipay.trade.wap.pay';
                $productCode = 'QUICK_WAP_WAY';
                $payType = 'wap';
            } else {
                $method = 'alipay.trade.page.pay';
                $productCode = 'FAST_INSTANT_TRADE_PAY';
                $payType = 'page';
            }

            $bizContent = [
                'subject' => $params['description'] ?? '商品支付',
                'out_trade_no' => $params['out_trade_no'],
                'total_amount' => number_format($params['amount'], 2, '.', ''),
                'product_code' => $productCode,
            ];

            if ($isMobile) {
                $bizContent['quit_url'] = $params['quit_url'] ?? config('app.url') . '/orders';
            }

            $notifyUrl = $params['notify_url'] ?? ($this->config['notify_url'] ?? config('app.url') . '/api/v1/payment/notify/alipay');
            $returnUrl = $params['return_url'] ?? ($this->config['return_url'] ?? config('app.url') . '/api/v1/payment/return/alipay');
            $outTradeNo = $params['out_trade_no'];

            $reqParams = [
                'app_id' => $this->config['app_id'],
                'method' => $method,
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA2',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'notify_url' => $notifyUrl,
                'return_url' => $returnUrl,
                'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE),
            ];

            $reqParams['sign'] = $this->sign($reqParams);
            $payUrl = $gateway . '?' . http_build_query($reqParams);

            Log::info('支付宝下单成功', [
                'out_trade_no' => $outTradeNo,
                'amount' => $params['amount'],
                'method' => $method,
                'is_mobile' => $isMobile,
            ]);

            return [
                'success' => true,
                'pay_url' => $payUrl,
                'out_trade_no' => $outTradeNo,
                'pay_type' => $payType,
                'is_mobile' => $isMobile,
            ];
        } catch (\Exception $e) {
            Log::error('支付宝下单异常', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 支付回调验签（异步通知 notify）
     */
    public function verifyNotify($data)
    {
        // 生产环境配置不完整：拒绝回调
        if ($this->isProduction() && !$this->isConfigured()) {
            Log::warning('支付宝生产环境配置不完整，拒绝回调处理');
            return ['success' => false, 'message' => '支付宝未配置'];
        }

        if ($this->isSandbox) {
            Log::info('支付宝沙箱模式回调', $data);
            return [
                'success' => true,
                'out_trade_no' => $data['out_trade_no'] ?? '',
                'transaction_id' => $data['trade_no'] ?? 'MOCK' . time(),
                'amount' => $data['total_amount'] ?? 0,
            ];
        }

        try {
            $sign = $data['sign'] ?? '';
            unset($data['sign'], $data['sign_type']);
            ksort($data);
            $message = urldecode(http_build_query($data));
            $publicKey = $this->config['alipay_public_key'];
            // Convert base64 public key to PEM format if needed
            if (strpos($publicKey, '-----BEGIN PUBLIC KEY-----') === false) {
                $publicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
            }
            $verified = openssl_verify($message, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);

            if (!$verified) {
                Log::warning('支付宝回调验签失败', ['data' => $data]);
                return ['success' => false, 'message' => '验签失败'];
            }

            return [
                'success' => true,
                'out_trade_no' => $data['out_trade_no'] ?? '',
                'transaction_id' => $data['trade_no'] ?? '',
                'amount' => $data['total_amount'] ?? 0,
                'trade_status' => $data['trade_status'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('支付宝回调验签异常', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 同步返回验签（return_url，GET参数）
     */
    public function verifyReturn($data)
    {
        return $this->verifyNotify($data);
    }

    /**
     * 退款
     */
    public function refund(array $params)
    {
        if ($this->isProduction() && !$this->isConfigured()) {
            return ['success' => false, 'message' => '支付宝未配置'];
        }

        if ($this->isSandbox) {
            Log::info('支付宝沙箱模式退款', $params);
            return $this->mockRefundResult($params['out_trade_no'], $params['out_refund_no'], $params['amount']);
        }

        try {
            $gateway = $this->config['gateway_url'] ?? 'https://openapi.alipay.com/gateway.do';
            $bizContent = [
                'out_trade_no' => $params['out_trade_no'],
                'refund_amount' => number_format($params['amount'], 2, '.', ''),
                'refund_reason' => $params['reason'] ?? '退款',
                'out_request_no' => $params['out_refund_no'],
            ];

            $reqParams = [
                'app_id' => $this->config['app_id'],
                'method' => 'alipay.trade.refund',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA2',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE),
            ];
            $reqParams['sign'] = $this->sign($reqParams);

            $response = Http::get($gateway . '?' . http_build_query($reqParams));
            $result = $response->json();
            $refundResult = $result['alipay_trade_refund_response'] ?? [];

            if (($refundResult['code'] ?? '') === '10000') {
                return [
                    'success' => true,
                    'refund_id' => $refundResult['trade_no'] ?? '',
                    'out_refund_no' => $refundResult['out_request_no'] ?? '',
                ];
            }
            return ['success' => false, 'message' => $refundResult['sub_msg'] ?? '退款失败'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 查询订单
     */
    public function queryOrder($outTradeNo)
    {
        if ($this->isSandbox) {
            return ['success' => true, 'trade_status' => 'TRADE_SUCCESS', 'out_trade_no' => $outTradeNo];
        }
        try {
            $gateway = $this->config['gateway_url'] ?? 'https://openapi.alipay.com/gateway.do';
            $bizContent = ['out_trade_no' => $outTradeNo];
            $reqParams = [
                'app_id' => $this->config['app_id'],
                'method' => 'alipay.trade.query',
                'charset' => 'utf-8',
                'sign_type' => 'RSA2',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'biz_content' => json_encode($bizContent),
            ];
            $reqParams['sign'] = $this->sign($reqParams);
            $response = Http::post($gateway, $reqParams);
            $result = $response->json()['alipay_trade_query_response'] ?? [];
            return ['success' => true] + $result;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * RSA2签名
     */
    private function sign($params)
    {
        ksort($params);
        $message = urldecode(http_build_query($params));
        $privateKey = $this->config['merchant_private_key'];
        openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }
}
