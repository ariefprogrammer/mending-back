<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Console\Command;

class DowngradeExpiredSubscriptions extends Command
{
    protected $signature = 'app:downgrade-expired-subscriptions';

    protected $description = 'Turunkan owner ke plan Free otomatis kalau trial atau masa aktif Premium-nya sudah habis';

    public function handle(): int
    {
        $freePlan = Plan::where('is_default', true)->first();

        if (! $freePlan) {
            $this->error('Plan default (Free) belum di-set. Batalkan proses.');
            return self::FAILURE;
        }

        $expiredSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereHas('plan', function ($query) {
                $query->where('slug', '!=', 'free');
            })
            ->with('plan', 'user')
            ->get()
            ->filter(function (Subscription $subscription) {
                // Trial habis
                if ($subscription->plan->slug === 'trial_premium' && $subscription->isTrialExpired()) {
                    return true;
                }

                // Premium habis (ends_at terlewati)
                if ($subscription->plan->slug === 'premium' && $subscription->ends_at && $subscription->ends_at->isPast()) {
                    return true;
                }

                return false;
            });

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('Tidak ada langganan yang perlu diturunkan.');
            return self::SUCCESS;
        }

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);

            $subscription->user->subscriptions()->create([
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'started_at' => now(),
            ]);

            $this->info("Owner {$subscription->user->name} ({$subscription->user->email}) diturunkan ke Free.");
        }

        $this->info("Selesai. {$expiredSubscriptions->count()} langganan diturunkan ke Free.");

        return self::SUCCESS;
    }
}