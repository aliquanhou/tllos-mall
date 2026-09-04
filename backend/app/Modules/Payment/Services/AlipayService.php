<?php
namespace App\Modules\Payment\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
     * 支付宝下单（手机网站支付）
     */
    public function unifiedOrder(array $params)
    {
        if ($this->isSandbox) {
            Log::info('支付宝沙箱模式下单', $params);
            return $this->mockPayResult($params['out_trade_no'], $params['amount']);
        }

        try {
            $gateway = $this->config['gateway_url'] ?? 'https://openapi.alipay.com/gateway.do';
            $bizContent = [
                'subject' => $params['description'] ?? '商品支付',
                'out_trade_no' => $params['out_trade_no'],
                'total_amount' => number_format($params['amount'], 2, '.', ''),
                'product_code' => 'QUICK_WAP_WAY',
                'quit_url' => $params['quit_url'] ?? config('app.url'),
            ];

            $params = [
                'app_id' => $this->config['app_id'],
                'method' => 'alipay.trade.wap.pay',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA2',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'notify_url' => $params['notify_url'] ?? config('app.url') . '/api/v1/payment/notify/alipay',
                'return_url' => $params['return_url'] ?? config('app.url') . '/user/orders',
                'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE),
            ];

            $params['sign'] = $this->sign($params);
            $payUrl = $gateway . '?' . http_build_query($params);

            return [
                'success' => true,
                'pay_url' => $payUrl,
                'out_trade_no' => $params['out_trade_no'],
            ];
        } catch (\Exception $e) {
            Log::error('支付宝下单异常', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 支付回调验签
     */
    public function verifyNotify($data)
    {
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
            $signType = $data['sign_type'] ?? 'RSA2';
            unset($data['sign'], $data['sign_type']);
            ksort($data);
            $message = urldecode(http_build_query($data));
            $publicKey = $this->config['alipay_public_key'];
            $verified = openssl_verify($message, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);

            if (!$verified) {
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
            Log::error('支付宝回调验签失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 退款
     */
    public function refund(array $params)
    {
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

            $response = Http::post($gateway, $reqParams);
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
