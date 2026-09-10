<?php
namespace App\Modules\Payment\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class PaymentService
{
    protected $config;
    protected $isSandbox = true;

    public function __construct($config = null)
    {
        if ($config) {
            $this->config = $config;
        } else {
            $this->config = $this->loadConfig();
        }
        // 配置不完整时使用沙箱模式
        $this->isSandbox = $this->checkSandboxMode();
    }

    abstract protected function getPayCode();

    protected function loadConfig()
    {
        $record = DB::table('pay_configs')->where('code', $this->getPayCode())->first();
        if ($record && $record->config) {
            return json_decode($record->config, true);
        }
        return [];
    }

    protected function checkSandboxMode()
    {
        if (empty($this->config)) return true;
        // 检查关键配置是否存在
        return false;
    }

    public function isSandbox()
    {
        return $this->isSandbox;
    }

    /**
     * 统一下单
     */
    abstract public function unifiedOrder(array $params);

    /**
     * 支付回调验签
     */
    abstract public function verifyNotify($data);

    /**
     * 退款
     */
    abstract public function refund(array $params);

    /**
     * 查询订单
     */
    abstract public function queryOrder($outTradeNo);

    /**
     * 生成模拟支付结果（沙箱模式）
     */
    protected function mockPayResult($outTradeNo, $amount)
    {
        return [
            'success' => true,
            'out_trade_no' => $outTradeNo,
            'transaction_id' => 'MOCK' . date('YmdHis') . rand(1000, 9999),
            'amount' => $amount,
            'pay_time' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 生成模拟退款结果
     */
    protected function mockRefundResult($outTradeNo, $outRefundNo, $amount)
    {
        return [
            'success' => true,
            'out_trade_no' => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'refund_id' => 'REFUND' . date('YmdHis') . rand(1000, 9999),
            'refund_amount' => $amount,
            'refund_time' => date('Y-m-d H:i:s'),
        ];
    }
}
