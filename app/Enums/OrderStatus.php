<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Tailwind classes for the light status pill, light and dark mode.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-500/15 dark:text-neutral-300',
            self::Confirmed => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/15 dark:text-indigo-300',
            self::Processing => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300',
            self::Shipped => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
            self::Delivered => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
            self::Cancelled => 'bg-rose-100 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
            self::Refunded => 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-300',
        };
    }

    /**
     * Statuses this one may legally move to.
     *
     * @return array<int, self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::Refunded],
            self::Delivered => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->transitions(), true);
    }

    /**
     * Orders still moving through fulfilment, so still needing operator attention.
     */
    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function open(): array
    {
        return [self::Pending, self::Confirmed, self::Processing, self::Shipped];
    }
}
