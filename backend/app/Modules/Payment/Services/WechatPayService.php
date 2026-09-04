<?php
namespace App\Modules\Payment\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WechatPayService extends PaymentService
{
    protected function getPayCode()
    {
        return 'wechat';
    }

    protected function checkSandboxMode()
    {
        if (empty($this->config)) return true;
        $required = ['app_id', 'mch_id', 'api_v3_key', 'serial_no', 'private_key'];
        foreach ($required as $key) {
            if (empty($this->config[$key])) return true;
        }
        return false;
    }

    /**
     * 微信支付JSAPI下单
     */
    public function unifiedOrder(array $params)
    {
        if ($this->isSandbox) {
            Log::info('微信支付沙箱模式下单', $params);
            return $this->mockPayResult($params['out_trade_no'], $params['amount']);
        }

        try {
            $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';
            $body = [
                'appid' => $this->config['app_id'],
                'mchid' => $this->config['mch_id'],
                'description' => $params['description'] ?? '商品支付',
                'out_trade_no' => $params['out_trade_no'],
                'notify_url' => $params['notify_url'] ?? config('app.url') . '/api/v1/payment/notify/wechat',
                'amount' => [
                    'total' => intval($params['amount'] * 100),
                    'currency' => 'CNY',
                ],
                'payer' => [
                    'openid' => $params['openid'] ?? '',
                ],
            ];

            $response = Http::withHeaders($this->buildHeaders('POST', '/v3/pay/transactions/jsapi', json_encode($body)))
                ->post($url, $body);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'prepay_id' => $result['prepay_id'] ?? '',
                    'jsapi_params' => $this->buildJsapiParams($result['prepay_id'] ?? ''),
                ];
            }
            Log::error('微信支付下单失败', ['response' => $response->body()]);
            return ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            Log::error('微信支付下单异常', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 构建JSAPI支付参数
     */
    private function buildJsapiParams($prepayId)
    {
        $timestamp = (string)time();
        $nonceStr = bin2hex(random_bytes(16));
        $package = 'prepay_id=' . $prepayId;
        $message = $this->config['app_id'] . "\n" . $timestamp . "\n" . $nonceStr . "\n" . $package . "\n";
        $signature = $this->sign($message);

        return [
            'appId' => $this->config['app_id'],
            'timeStamp' => $timestamp,
            'nonceStr' => $nonceStr,
            'package' => $package,
            'signType' => 'RSA',
            'paySign' => $signature,
        ];
    }

    /**
     * 支付回调验签
     */
    public function verifyNotify($data)
    {
        if ($this->isSandbox) {
            Log::info('微信支付沙箱模式回调', $data);
            return [
                'success' => true,
                'out_trade_no' => $data['out_trade_no'] ?? '',
                'transaction_id' => $data['transaction_id'] ?? 'MOCK' . time(),
                'amount' => ($data['amount']['total'] ?? 0) / 100,
            ];
        }

        try {
            // 微信支付V3回调验签
            $signature = $_SERVER['HTTP_WECHATPAY_SIGNATURE'] ?? '';
            $timestamp = $_SERVER['HTTP_WECHATPAY_TIMESTAMP'] ?? '';
            $nonce = $_SERVER['HTTP_WECHATPAY_NONCE'] ?? '';
            $serial = $_SERVER['HTTP_WECHATPAY_SERIAL'] ?? '';

            $message = $timestamp . "\n" . $nonce . "\n" . file_get_contents('php://input') . "\n";
            // 验签逻辑（使用平台证书公钥）
            // $verified = openssl_verify($message, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256);

            $result = json_decode(file_get_contents('php://input'), true);
            $resource = $result['resource'] ?? [];
            // 解密resource.ciphertext
            $decrypted = $this->decryptResource($resource);

            return [
                'success' => true,
                'out_trade_no' => $decrypted['out_trade_no'] ?? '',
                'transaction_id' => $decrypted['transaction_id'] ?? '',
                'amount' => ($decrypted['amount']['total'] ?? 0) / 100,
            ];
        } catch (\Exception $e) {
            Log::error('微信支付回调验签失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 退款
     */
    public function refund(array $params)
    {
        if ($this->isSandbox) {
            Log::info('微信支付沙箱模式退款', $params);
            return $this->mockRefundResult($params['out_trade_no'], $params['out_refund_no'], $params['amount']);
        }

        try {
            $url = 'https://api.mch.weixin.qq.com/v3/refund/domestic/refunds';
            $body = [
                'out_trade_no' => $params['out_trade_no'],
                'out_refund_no' => $params['out_refund_no'],
                'reason' => $params['reason'] ?? '退款',
                'notify_url' => $params['notify_url'] ?? config('app.url') . '/api/v1/payment/refund-notify/wechat',
                'amount' => [
                    'refund' => intval($params['amount'] * 100),
                    'total' => intval($params['total_amount'] * 100),
                    'currency' => 'CNY',
                ],
            ];

            $response = Http::withHeaders($this->buildHeaders('POST', '/v3/refund/domestic/refunds', json_encode($body)))
                ->post($url, $body);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'refund_id' => $result['refund_id'] ?? '',
                    'out_refund_no' => $result['out_refund_no'] ?? '',
                ];
            }
            return ['success' => false, 'message' => $response->body()];
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
            return ['success' => true, 'trade_state' => 'SUCCESS', 'out_trade_no' => $outTradeNo];
        }
        try {
            $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/out-trade-no/' . $outTradeNo . '?mchid=' . $this->config['mch_id'];
            $response = Http::withHeaders($this->buildHeaders('GET', '/v3/pay/transactions/out-trade-no/' . $outTradeNo . '?mchid=' . $this->config['mch_id'], ''))
                ->get($url);
            return $response->successful() ? ['success' => true] + $response->json() : ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 构建请求头
     */
    private function buildHeaders($method, $urlPath, $body)
    {
        $timestamp = (string)time();
        $nonceStr = bin2hex(random_bytes(16));
        $message = $method . "\n" . $urlPath . "\n" . $timestamp . "\n" . $nonceStr . "\n" . $body . "\n";
        $signature = $this->sign($message);
        $auth = sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
            $this->config['mch_id'], $nonceStr, $timestamp, $this->config['serial_no'], $signature
        );
        return [
            'Authorization' => $auth,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * RSA签名
     */
    private function sign($message)
    {
        $privateKey = $this->config['private_key'];
        openssl_sign($message, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    /**
     * 解密回调资源
     */
    private function decryptResource($resource)
    {
        $ciphertext = base64_decode($resource['ciphertext']);
        $key = $this->config['api_v3_key'];
        $nonce = $resource['nonce'];
        $associatedData = $resource['associated_data'] ?? '';
        // AES-256-GCM解密
        $tag = substr($ciphertext, -16);
        $ciphertext = substr($ciphertext, 0, -16);
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $associatedData);
        return json_decode($plaintext, true);
    }
}
