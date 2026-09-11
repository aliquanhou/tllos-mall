<?php

namespace App\Modules\Refund\Adapters;

use App\Modules\Refund\Contracts\RefundProviderInterface;
use Illuminate\Contracts\Container\Container;

/**
 * P1-PI-01: Refund Provider Factory
 *
 * Resolves the correct RefundProviderInterface implementation
 * based on the provider name (alipay / wechat).
 *
 * Usage:
 *   $provider = $factory->make('alipay');
 *   $result = $provider->refund([...]);
 *
 * New providers can be added by implementing RefundProviderInterface
 * and registering them here.
 */
class RefundProviderFactory
{
    /** @var array<string, class-string<RefundProviderInterface>> */
    private const PROVIDER_MAP = [
        'alipay' => AlipayRefundAdapter::class,
        'wechat' => WechatRefundAdapter::class,
    ];

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Get the refund provider adapter for the given provider name.
     */
    public function make(string $provider): ?RefundProviderInterface
    {
        $class = self::PROVIDER_MAP[$provider] ?? null;
        if (!$class) {
            return null;
        }
        return $this->container->make($class);
    }

    /**
     * Get all registered provider names.
     *
     * @return string[]
     */
    public function getAvailableProviders(): array
    {
        return array_keys(self::PROVIDER_MAP);
    }

    /**
     * Check if a provider is registered.
     */
    public function has(string $provider): bool
    {
        return isset(self::PROVIDER_MAP[$provider]);
    }
}
