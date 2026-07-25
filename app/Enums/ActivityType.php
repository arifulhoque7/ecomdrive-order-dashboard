<?php

namespace App\Enums;

enum ActivityType: string
{
    case OrderPlaced = 'order_placed';
    case StatusChanged = 'status_changed';
    case Note = 'note';
    case Payment = 'payment';
    case AiInsight = 'ai_insight';

    public function label(): string
    {
        return match ($this) {
            self::OrderPlaced => 'Order placed',
            self::StatusChanged => 'Status changed',
            self::Note => 'Note',
            self::Payment => 'Payment',
            self::AiInsight => 'AI insight',
        };
    }
}
