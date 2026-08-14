<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function status(Request $request)
    {
        $authUser = $request->user();

        // Tentukan siapa owner-nya: kalau yang login owner langsung,
        // kalau employee, ambil owner lewat outlet->user
        $ownerUser = null;

        if ($authUser instanceof User) {
            $ownerUser = $authUser;
        } elseif ($authUser instanceof Employee) {
            $ownerUser = $authUser->outlet?->user;
        }

        if (! $ownerUser) {
            return response()->json([
                'status' => 'success',
                'data' => null,
            ]);
        }

        $subscription = $ownerUser->currentSubscription()->with('plan')->first();

        if (! $subscription) {
            return response()->json([
                'status' => 'success',
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'plan_slug'               => $subscription->plan->slug,
                'plan_name'               => $subscription->plan->name,
                'transaction_limit'       => $subscription->plan->transaction_limit,
                'remaining_transactions'  => $subscription->remainingTransactions(),
                'trial_ends_at'           => $subscription->trial_ends_at?->toIso8601String(),
                'ends_at'                 => $subscription->ends_at?->toIso8601String(),
                'is_trial_expired'        => $subscription->isTrialExpired(),
                'allowed_menus'           => $subscription->plan->allowed_menus,
            ],
        ]);
    }
}