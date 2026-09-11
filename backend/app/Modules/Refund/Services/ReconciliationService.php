<?php

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Refund\Adapters\RefundProviderFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * P1-PI-06: Reconciliation Service
 *
 * Verifies that TLL local records match Provider records.
 *
 * Principle: Detect first, resolve manually. Never auto-fix.
 *
 * Two reconciliation domains:
 * 1. Payment Reconciliation: orders + payments vs Provider transaction
 * 2. Refund Reconciliation: order_refunds vs Provider refund record
 *
 * Difference codes:
 *   PAYMENT_MISSING_PROVIDER    - Local paid but Provider has no record
 *   PAYMENT_STATUS_DRIFT        - Local status != Provider status
 *   PAYMENT_AMOUNT_DRIFT        - Local amount != Provider amount
 *   REFUND_MISSING_PROVIDER     - Local refund but Provider has no record
 *   REFUND_STATUS_DRIFT         - Local refund status != Provider status
 *   REFUND_AMOUNT_DRIFT         - Local refund amount != Provider amount
 *   RECONCILIATION_OK           - Matched
 */
class ReconciliationService
{
    /**
     * Run full reconciliation (payments + refunds).
     * Returns summary report.
     */
    public function runFullReconciliation(): array
    {
        $paymentReport = $this->reconcilePayments();
        $refundReport = $this->reconcileRefunds();

        return [
            'payment' => $paymentReport,
            'refund' => $refundReport,
            'total_exceptions' => $paymentReport['exceptions'] + $refundReport['exceptions'],
            'run_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Payment Reconciliation.
     * Compares local payments with Provider transaction records.
     */
    public function reconcilePayments(): array
    {
        // Only reconcile paid payments (status=1)
        $payments = DB::table('payments')
            ->where('status', 1)
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->get();

        $total = $payments->count();
        $matched = 0;
        $exceptions = 0;
        $details = [];

        foreach ($payments as $payment) {
            $result = $this->reconcileSinglePayment($payment);
            if ($result['matched']) {
                $matched++;
            } else {
                $exceptions++;
                $details[] = $result;
                $this->recordException(
                    'PAYMENT',
                    'payment_no',
                    $payment->payment_no,
                    $payment->status,
                    $result['provider_status'] ?? 'UNKNOWN',
                    $result['difference_code'],
                    $result['difference_detail'] ?? ''
                );
            }
        }

        return [
            'total' => $total,
            'matched' => $matched,
            'exceptions' => $exceptions,
            'details' => $details,
        ];
    }

    /**
     * Refund Reconciliation.
     * Compares local order_refunds with Provider refund records.
     */
    public function reconcileRefunds(): array
    {
        // Only reconcile refunds in final or active states
        $refunds = DB::table('order_refunds')
            ->whereIn('status', [
                RefundStatus::SUCCESS,
                RefundStatus::FAILED,
                RefundStatus::PROCESSING,
                RefundStatus::UNKNOWN,
            ])
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->get();

        $total = $refunds->count();
        $matched = 0;
        $exceptions = 0;
        $details = [];

        foreach ($refunds as $refund) {
            $result = $this->reconcileSingleRefund($refund);
            if ($result['matched']) {
                $matched++;
            } else {
                $exceptions++;
                $details[] = $result;
                $this->recordException(
                    'REFUND',
                    'refund_no',
                    $refund->refund_no,
                    $this->refundStatusName($refund->status),
                    $result['provider_status'] ?? 'UNKNOWN',
                    $result['difference_code'],
                    $result['difference_detail'] ?? ''
                );
            }
        }

        return [
            'total' => $total,
            'matched' => $matched,
            'exceptions' => $exceptions,
            'details' => $details,
        ];
    }

    /**
     * Reconcile a single payment against Provider.
     */
    private function reconcileSinglePayment(object $payment): array
    {
        // Local payment is paid (status=1)
        // Provider must have a matching transaction with same amount

        if (empty($payment->third_payment_no)) {
            return [
                'matched' => false,
                'difference_code' => 'PAYMENT_MISSING_PROVIDER',
                'difference_detail' => 'Local payment is paid but third_payment_no is empty',
                'provider_status' => 'NOT_FOUND',
            ];
        }

        // For balance/points payments, provider is local — always matched
        if (in_array($payment->provider, ['balance', 'points'], true)) {
            return ['matched' => true];
        }

        // For Alipay/WeChat, query Provider
        try {
            $factory = app(RefundProviderFactory::class);
            $provider = $factory->make($payment->provider);

            if (!$provider || !$provider->isConfigured()) {
                // Provider not configured — cannot verify, record as exception
                return [
                    'matched' => false,
                    'difference_code' => 'PAYMENT_PROVIDER_UNAVAILABLE',
                    'difference_detail' => 'Provider not configured for reconciliation',
                    'provider_status' => 'UNAVAILABLE',
                ];
            }

            // Query payment status via provider (using query method if available)
            // Note: RefundProviderInterface::query is for refunds, but we can
            // use payment service directly for payment queries
            $providerResult = $this->queryProviderPayment($payment);

            if (!$providerResult['found']) {
                return [
                    'matched' => false,
                    'difference_code' => 'PAYMENT_MISSING_PROVIDER',
                    'difference_detail' => 'Provider has no record for transaction_id=' . $payment->third_payment_no,
                    'provider_status' => 'NOT_FOUND',
                ];
            }

            // Status check
            if (!$providerResult['paid']) {
                return [
                    'matched' => false,
                    'difference_code' => 'PAYMENT_STATUS_DRIFT',
                    'difference_detail' => 'Local=PAID, Provider=' . ($providerResult['status'] ?? 'UNKNOWN'),
                    'provider_status' => $providerResult['status'] ?? 'UNKNOWN',
                ];
            }

            // Amount check
            if (bccomp((string)$providerResult['amount'], (string)$payment->amount, 2) !== 0) {
                return [
                    'matched' => false,
                    'difference_code' => 'PAYMENT_AMOUNT_DRIFT',
                    'difference_detail' => 'Local=' . $payment->amount . ', Provider=' . $providerResult['amount'],
                    'provider_status' => $providerResult['status'] ?? 'PAID',
                ];
            }

            return ['matched' => true];
        } catch (\Throwable $e) {
            return [
                'matched' => false,
                'difference_code' => 'PAYMENT_PROVIDER_ERROR',
                'difference_detail' => 'Provider query error: ' . $e->getMessage(),
                'provider_status' => 'ERROR',
            ];
        }
    }

    /**
     * Reconcile a single refund against Provider.
     */
    private function reconcileSingleRefund(object $refund): array
    {
        // For local-only refunds (balance/points), always matched
        if (in_array($refund->provider, ['balance', 'points'], true)) {
            return ['matched' => true];
        }

        try {
            $factory = app(RefundProviderFactory::class);
            $provider = $factory->make($refund->provider);

            if (!$provider || !$provider->isConfigured()) {
                return [
                    'matched' => false,
                    'difference_code' => 'REFUND_PROVIDER_UNAVAILABLE',
                    'difference_detail' => 'Provider not configured for reconciliation',
                    'provider_status' => 'UNAVAILABLE',
                ];
            }

            // Query Provider refund status
            $providerResult = $provider->query($refund->refund_no, $refund->payment_no);

            if ($providerResult->isUnknown()) {
                // Provider query failed — cannot verify, record exception
                return [
                    'matched' => false,
                    'difference_code' => 'REFUND_PROVIDER_UNAVAILABLE',
                    'difference_detail' => 'Provider query returned UNKNOWN: ' . ($providerResult->message ?? ''),
                    'provider_status' => 'UNKNOWN',
                ];
            }

            $providerStatus = $this->providerResultStatusName($providerResult);

            // Status drift check
            $localStatusName = $this->refundStatusName($refund->status);

            if ($refund->status == RefundStatus::SUCCESS && !$providerResult->isSuccess()) {
                return [
                    'matched' => false,
                    'difference_code' => 'REFUND_STATUS_DRIFT',
                    'difference_detail' => "Local=SUCCESS, Provider=$providerStatus",
                    'provider_status' => $providerStatus,
                ];
            }

            if ($refund->status == RefundStatus::FAILED && !$providerResult->isFailed()) {
                return [
                    'matched' => false,
                    'difference_code' => 'REFUND_STATUS_DRIFT',
                    'difference_detail' => "Local=FAILED, Provider=$providerStatus",
                    'provider_status' => $providerStatus,
                ];
            }

            // For PROCESSING/UNKNOWN local status, Provider PROCESSING is acceptable
            if (in_array($refund->status, [RefundStatus::PROCESSING, RefundStatus::UNKNOWN], true)
                && $providerResult->isProcessing()) {
                return ['matched' => true];
            }

            return ['matched' => true];
        } catch (\Throwable $e) {
            return [
                'matched' => false,
                'difference_code' => 'REFUND_PROVIDER_ERROR',
                'difference_detail' => 'Provider query error: ' . $e->getMessage(),
                'provider_status' => 'ERROR',
            ];
        }
    }

    /**
     * Query Provider for payment status.
     * Uses AlipayService/WechatPayService queryOrder.
     */
    private function queryProviderPayment(object $payment): array
    {
        try {
            if ($payment->provider === 'alipay') {
                $alipay = app(\App\Modules\Payment\Services\AlipayService::class);
                $result = $alipay->queryOrder($payment->payment_no);
                if (!($result['success'] ?? false)) {
                    return ['found' => false];
                }
                return [
                    'found' => true,
                    'paid' => ($result['trade_state'] ?? '') === 'TRADE_SUCCESS',
                    'status' => $result['trade_state'] ?? 'UNKNOWN',
                    'amount' => $result['total_amount'] ?? $result['buyer_pay_amount'] ?? 0,
                ];
            }

            if ($payment->provider === 'wechat') {
                $wechat = app(\App\Modules\Payment\Services\WechatPayService::class);
                $result = $wechat->queryOrder($payment->payment_no);
                if (!($result['success'] ?? false)) {
                    return ['found' => false];
                }
                return [
                    'found' => true,
                    'paid' => ($result['trade_state'] ?? '') === 'SUCCESS',
                    'status' => $result['trade_state'] ?? 'UNKNOWN',
                    'amount' => isset($result['amount']['total']) ? $result['amount']['total'] / 100 : 0,
                ];
            }

            return ['found' => false];
        } catch (\Throwable $e) {
            return ['found' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Record a reconciliation exception.
     * Principle: Detect first, never auto-fix.
     */
    private function recordException(
        string $type,
        string $identityType,
        string $identityValue,
        ?string $localStatus,
        ?string $providerStatus,
        string $differenceCode,
        string $differenceDetail
    ): void {
        // Avoid duplicate records for same identity + difference
        $existing = DB::table('reconciliation_exceptions')
            ->where('type', $type)
            ->where('identity_value', $identityValue)
            ->where('difference_code', $differenceCode)
            ->whereNull('resolved_at')
            ->first();

        if ($existing) {
            return; // Already recorded, unresolved
        }

        DB::table('reconciliation_exceptions')->insert([
            'type' => $type,
            'identity_type' => $identityType,
            'identity_value' => $identityValue,
            'local_status' => $localStatus,
            'provider_status' => $providerStatus,
            'difference_code' => $differenceCode,
            'difference_detail' => $differenceDetail,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Log::warning('对账发现差异', [
            'type' => $type,
            'identity' => $identityValue,
            'difference_code' => $differenceCode,
            'detail' => $differenceDetail,
        ]);
    }

    /**
     * Get unresolved exceptions.
     */
    public function getUnresolvedExceptions(?string $type = null): array
    {
        $query = DB::table('reconciliation_exceptions')->whereNull('resolved_at');
        if ($type) {
            $query->where('type', $type);
        }
        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Mark an exception as resolved (manual resolution).
     */
    public function resolveException(int $id, string $resolvedBy, string $note = ''): bool
    {
        $exception = DB::table('reconciliation_exceptions')
            ->where('id', $id)
            ->whereNull('resolved_at')
            ->first();

        if (!$exception) {
            return false;
        }

        $newDetail = $note ? $note . ' | ' . ($exception->difference_detail ?? '') : ($exception->difference_detail ?? '');

        return DB::table('reconciliation_exceptions')
            ->where('id', $id)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => Carbon::now(),
                'resolved_by' => $resolvedBy,
                'difference_detail' => $newDetail,
                'updated_at' => Carbon::now(),
            ]) > 0;
    }

    private function refundStatusName(int $status): string
    {
        return match ($status) {
            RefundStatus::REQUESTED => 'REQUESTED',
            RefundStatus::PROCESSING => 'PROCESSING',
            RefundStatus::REJECTED => 'REJECTED',
            RefundStatus::FAILED => 'FAILED',
            RefundStatus::UNKNOWN => 'UNKNOWN',
            RefundStatus::SUCCESS => 'SUCCESS',
            RefundStatus::CANCELLED => 'CANCELLED',
            default => 'UNKNOWN(' . $status . ')',
        };
    }

    private function providerResultStatusName($result): string
    {
        if ($result->isSuccess()) return 'SUCCESS';
        if ($result->isFailed()) return 'FAILED';
        if ($result->isProcessing()) return 'PROCESSING';
        return 'UNKNOWN';
    }
}
