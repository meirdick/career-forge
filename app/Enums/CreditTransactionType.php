<?php

namespace App\Enums;

enum CreditTransactionType: string
{
    case Purchase = 'purchase';
    case Consumption = 'consumption';
    case Refund = 'refund';
    case Bonus = 'bonus';
}
