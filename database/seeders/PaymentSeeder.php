<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class PaymentSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $payments = [
            ['tag' => 'cash',         'input' => 1],  //input sort in ui
            ['tag' => 'wallet',       'input' => 2],  //input sort in ui
            ['tag' => 'paytabs',      'input' => 3],  //input sort in ui
            ['tag' => 'flutterWave',  'input' => 4],  //input sort in ui
            ['tag' => 'paystack',     'input' => 5],  //input sort in ui
            ['tag' => 'mercado-pago', 'input' => 6],  //input sort in ui
            ['tag' => 'razorpay',     'input' => 7],  //input sort in ui
            ['tag' => 'stripe',       'input' => 8],  //input sort in ui
            ['tag' => 'paypal',       'input' => 9],  //input sort in ui
            ['tag' => 'moya-sar',     'input' => 10], //input sort in ui
            ['tag' => 'mollie',       'input' => 11], //input sort in ui
        ];

        foreach ($payments as $payment) {
            try {
                Payment::updateOrCreate([
                    'tag'   => data_get($payment, 'tag')
                ], [
                    'input' => data_get($payment, 'input')
                ]);
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }

}
