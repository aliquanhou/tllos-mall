<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Services\ReconciliationService;
use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Refund\Adapters\AlipayRefundAdapter;
use App\Modules\Payment\Services\AlipayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Mockery;

/**
 * P1-PI-06: Reconciliation Tests
 *
 * Verifies:
 * - Payment reconciliation (local vs provider)
 * - Refund reconciliation (local vs provider)
 * - Exception recording (detect, never auto-fix)
 * - Exception resolution (manual)
 */
class ReconciliationTest extends TestCase
{
    private $testUserId;
    private $testOrderId;
    private $testOrderNo;
    private $testPayNo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUserId = DB::table('users')->insertGetId([
            'name' => 'RECTest',
            'email' => 'rec_' . uniqid() . '@test.com',
            'password' => bcrypt('test123'),
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $this->testOrderNo = 'REC' . date('YmdHis') . rand(1000, 9999);
        $this->testOrderId = DB::table('orders')->insertGetId([
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'total_amount' => 100.00, 'pay_amount' => 100.00,
            'status' => 1, 'pay_type' => 2,
            'receiver_name' => 'T', 'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => 'GD', 'city_name' => 'HZ', 'district_name' => 'DY',
            'receiver_address' => 'T',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $this->testPayNo = 'PAYREC' . uniqid();
        DB::table('payments')->insert([
            'payment_no' => $this->testPayNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 100.00,
            'status' => 1, 'third_payment_no' => 'TXNREC' . uniqid(),
            'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('reconciliation_exceptions')->where('identity_value', 'like', '%REC%')->delete();
        DB::table('order_refunds')->where('user_id', $this->testUserId)->delete();
        DB::table('payments')->where('user_id', $this->testUserId)->delete();
        DB::table('orders')->where('user_id', $this->testUserId)->delete();
        DB::table('users')->where('id', $this->testUserId)->delete();
        Mockery::close();
        parent::tearDown();
    }

    // ============================================================
    // Payment Reconciliation Tests
    // ============================================================

    public function test_payment_reconciliation_matched()
    {
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryOrder')->once()->with($this->testPayNo)->andReturn([
            'success' => true,
            'trade_state' => 'TRADE_SUCCESS',
            'total_amount' => '100.00',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $service = app(ReconciliationService::class);
        $report = $service->reconcilePayments();

        // Our test payment should be matched
        $this->assertGreaterThanOrEqual(1, $report['total']);
        $this->assertGreaterThanOrEqual(1, $report['matched']);
    }

    public function test_payment_missing_provider_recorded_as_exception()
    {
        // Create a paid payment with no provider transaction_id
        $payNo = 'PAYMISS' . time();
        DB::table('payments')->insert([
            'payment_no' => $payNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 50.00,
            'status' => 1, 'third_payment_no' => '', // missing
            'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $service = app(ReconciliationService::class);
        $report = $service->reconcilePayments();

        // Should find at least one exception (the missing provider one)
        $this->assertGreaterThanOrEqual(1, $report['exceptions']);

        // Exception should be recorded
        $exception = DB::table('reconciliation_exceptions')
            ->where('identity_value', $payNo)
            ->where('difference_code', 'PAYMENT_MISSING_PROVIDER')
            ->first();
        $this->assertNotNull($exception);
        $this->assertEquals('PAYMENT', $exception->type);
    }

    public function test_payment_status_drift_recorded()
    {
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryOrder')->andReturn([
            'success' => true,
            'trade_state' => 'TRADE_CLOSED', // Provider says closed, local says paid
            'total_amount' => '100.00',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $service = app(ReconciliationService::class);
        $report = $service->reconcilePayments();

        $exception = DB::table('reconciliation_exceptions')
            ->where('identity_value', $this->testPayNo)
            ->where('difference_code', 'PAYMENT_STATUS_DRIFT')
            ->first();
        $this->assertNotNull($exception);
    }

    // ============================================================
    // Refund Reconciliation Tests
    // ============================================================

    public function test_refund_reconciliation_success_matched()
    {
        // Create a successful refund
        $refundService = app(RefundService::class);
        $result = $refundService->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 50.00,
            'reason' => 'recon test',
            'type' => 1,
        ]);
        $refundService->approveRefund($result['refund_id']);
        DB::table('order_refunds')->where('id', $result['refund_id'])->update([
            'status' => RefundStatus::SUCCESS,
            'refunded_at' => Carbon::now(),
        ]);

        // Mock Provider query to return SUCCESS
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_SUCCESS',
            'refund_amount' => '50.00',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $service = app(ReconciliationService::class);
        $report = $service->reconcileRefunds();

        $this->assertGreaterThanOrEqual(1, $report['total']);
    }

    public function test_refund_status_drift_recorded()
    {
        // Local says SUCCESS, Provider says FAILED
        $refundService = app(RefundService::class);
        $result = $refundService->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 50.00,
            'reason' => 'drift test',
            'type' => 1,
        ]);
        $refundService->approveRefund($result['refund_id']);
        DB::table('order_refunds')->where('id', $result['refund_id'])->update([
            'status' => RefundStatus::SUCCESS,
            'refunded_at' => Carbon::now(),
        ]);

        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_FAIL', // Provider says failed
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $service = app(ReconciliationService::class);
        $service->reconcileRefunds();

        $exception = DB::table('reconciliation_exceptions')
            ->where('identity_value', $result['refund_no'])
            ->where('difference_code', 'REFUND_STATUS_DRIFT')
            ->first();
        $this->assertNotNull($exception);
        $this->assertEquals('REFUND', $exception->type);
    }

    // ============================================================
    // Exception Management Tests
    // ============================================================

    public function test_exception_not_duplicated()
    {
        // Create a payment with missing provider
        $payNo = 'PAYDUP' . time();
        DB::table('payments')->insert([
            'payment_no' => $payNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 50.00,
            'status' => 1, 'third_payment_no' => '',
            'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $service = app(ReconciliationService::class);

        // Run reconciliation twice
        $service->reconcilePayments();
        $service->reconcilePayments();

        // Should only have one exception record
        $count = DB::table('reconciliation_exceptions')
            ->where('identity_value', $payNo)
            ->where('difference_code', 'PAYMENT_MISSING_PROVIDER')
            ->whereNull('resolved_at')
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_exception_can_be_resolved_manually()
    {
        // Create and record an exception
        $payNo = 'PAYRES' . time();
        DB::table('payments')->insert([
            'payment_no' => $payNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 50.00,
            'status' => 1, 'third_payment_no' => '',
            'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $service = app(ReconciliationService::class);
        $service->reconcilePayments();

        $exception = DB::table('reconciliation_exceptions')
            ->where('identity_value', $payNo)
            ->first();
        $this->assertNotNull($exception);

        // Resolve manually
        $resolved = $service->resolveException($exception->id, 'admin', 'verified manually');
        $this->assertTrue($resolved);

        // Should be resolved
        $resolvedException = DB::table('reconciliation_exceptions')->where('id', $exception->id)->first();
        $this->assertNotNull($resolvedException->resolved_at);
        $this->assertEquals('admin', $resolvedException->resolved_by);
    }

    public function test_get_unresolved_exceptions()
    {
        // Create an exception
        $payNo = 'PAYUNR' . time();
        DB::table('payments')->insert([
            'payment_no' => $payNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 50.00,
            'status' => 1, 'third_payment_no' => '',
            'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $service = app(ReconciliationService::class);
        $service->reconcilePayments();

        $unresolved = $service->getUnresolvedExceptions('PAYMENT');
        $this->assertGreaterThanOrEqual(1, count($unresolved));
    }

    // ============================================================
    // Full Reconciliation Test
    // ============================================================

    public function test_full_reconciliation_returns_summary()
    {
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryOrder')->andReturn([
            'success' => true,
            'trade_state' => 'TRADE_SUCCESS',
            'total_amount' => '100.00',
        ]);
        $mock->shouldReceive('queryRefund')->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_SUCCESS',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $service = app(ReconciliationService::class);
        $report = $service->runFullReconciliation();

        $this->assertArrayHasKey('payment', $report);
        $this->assertArrayHasKey('refund', $report);
        $this->assertArrayHasKey('total_exceptions', $report);
        $this->assertArrayHasKey('run_at', $report);
    }
}
