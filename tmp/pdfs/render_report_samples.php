<?php

use App\Models\Claim;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require_once dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = new User(['name' => 'Rahul Kulkarni', 'email' => 'rahul@example.com', 'phone' => '9876543210']);
$user->id = 101;
$paidPlan = new Plan(['name' => 'Gold Plan', 'slug' => 'gold', 'price' => 89900, 'duration_years' => 5, 'coverage_sqm' => 5]);
$paidPlan->id = 2;
$freePlan = new Plan(['name' => 'Free Plan', 'slug' => 'free', 'price' => 0, 'duration_days' => 15, 'coverage_sqm' => 5, 'is_free' => true]);
$freePlan->id = 1;

$kind = $argv[1] ?? null;
$outputDir = dirname(__DIR__, 2).'/output/pdf';

if ($kind === 'claims') {
    $subscription = new Subscription(['starts_at' => now()->subDays(4), 'ends_at' => now()->addYears(5), 'status' => 'active']);
    $subscription->setRelation('plan', $paidPlan);
    $claim = new Claim([
        'claim_number' => 'CLM-000123',
        'vehicle_make' => 'Toyota',
        'vehicle_model' => 'Fortuner',
        'registration_number' => 'MH12AB1234',
        'vehicle_year' => 2024,
        'panels' => ['front_bumper', 'left_fender'],
        'status' => 'pending',
    ]);
    $claim->created_at = now()->subDay();
    $claim->setRelation('user', $user);
    $claim->setRelation('subscription', $subscription);
    $claims = new Collection([$claim]);
    Pdf::loadView('admin.reports.claims', compact('claims'))->setPaper('a4', 'landscape')->save($outputDir.'/claims-report.pdf');
    exit(0);
}

if ($kind === 'payments') {
    $paid = new Payment(['gateway' => 'razorpay', 'gateway_order_id' => 'order_R4Z0R001', 'gateway_payment_id' => 'pay_R4Z0R001', 'amount' => 89900, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()->subDays(3)]);
    $paid->id = 42;
    $paid->created_at = now()->subDays(3);
    $paid->setRelation('user', $user);
    $paid->setRelation('plan', $paidPlan);
    $free = new Payment(['gateway' => 'free', 'gateway_order_id' => 'free_subscription_43', 'amount' => 0, 'currency' => 'USD', 'status' => 'paid', 'paid_at' => now()->subDay()]);
    $free->id = 43;
    $free->created_at = now()->subDay();
    $free->setRelation('user', $user);
    $free->setRelation('plan', $freePlan);
    $payments = new Collection([$paid, $free]);
    Pdf::loadView('admin.reports.payments', compact('payments'))->setPaper('a4', 'landscape')->save($outputDir.'/payments-report.pdf');
    exit(0);
}

fwrite(STDERR, "Expected claims or payments.\n");
exit(1);
