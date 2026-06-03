<?php

namespace App\Domain\Orders\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $product)
    {
        parent::__construct("Insufficient stock for product: {$product}");
    }
}
