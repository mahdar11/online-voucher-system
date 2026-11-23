<?php

namespace App\Modules\Ordering\Console;

use App\Modules\Ordering\Actions\ExpireOrders;
use Illuminate\Console\Command;

class ExpireOrdersCommand extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Kedaluwarsakan pesanan yang lewat batas bayar dan kembalikan vouchernya ke stok';

    public function handle(ExpireOrders $expireOrders): int
    {
        $count = $expireOrders->expireAllOverdue();

        $this->info("{$count} pesanan dikedaluwarsakan.");

        return self::SUCCESS;
    }
}
