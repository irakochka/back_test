<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\ReferralEarning;
use App\Services\Referral\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReferralController extends Controller
{
    public function attach(Request $request, ReferralService $referralService): JsonResponse
    {
        $master = $request->attributes->get('current_master');

        if (!$master) {
            return response()->json(['message' => 'Master not found'], Response::HTTP_NOT_FOUND);
        }

        $code = $request->input('code');

        if (!$code) {
            return response()->json([
                'message' => 'Referral code is required',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $referral = $referralService->registerReferral($master, $code);

        if (!$referral) {
            return response()->json([
                'message' => 'Referral code not found or self attach is forbidden',
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$referral->wasRecentlyCreated) {
            return response()->json([
                'message' => 'Master is already attached to a referral',
            ], Response::HTTP_CONFLICT);
        }

        return response()->json(null, Response::HTTP_CREATED);
    }

    public function my(Request $request): JsonResponse
    {
        $master = $request->attributes->get('current_master');

        if (!$master) {
            return response()->json(['message' => 'Master not found'], Response::HTTP_NOT_FOUND);
        }

        $referrals = Referral::where('referrer_master_id', $master->id)
            ->with('referredMaster:id,name')
            ->withSum('earnings as amount', 'amount')
            ->get();

        return response()->json($referrals->map(function ($referral) {
            return [
                'name' => $referral->referredMaster?->name,
                'attached_at' => $referral->created_at,
                'rewarded' => $referral->status === Referral::STATUS_REWARDED,
                'amount' => (int) $referral->amount,
            ];
        }));
    }

    public function earnings(Request $request): JsonResponse
    {
        $master = $request->attributes->get('current_master');

        if (!$master) {
            return response()->json(['message' => 'Master not found'], Response::HTTP_NOT_FOUND);
        }

        // сводка по деньгам: всего начислено, в ожидании, выплачено, сколько рефералов засчитано
        $referralEarnings = ReferralEarning::where('referrer_master_id', $master->id);

        $totalEarnings = (clone $referralEarnings)->sum('amount');

        $pending = (clone $referralEarnings)
            ->where('status', ReferralEarning::STATUS_PENDING)
            ->sum('amount');

        $paid = (clone $referralEarnings)
            ->where('status', ReferralEarning::STATUS_PAID)
            ->sum('amount');

        $rewardedCount = Referral::where('referrer_master_id', $master->id)
            ->where('status', Referral::STATUS_REWARDED)
            ->count();

        return response()->json([
            'total_earnings' => (int) $totalEarnings,
            'pending' => (int) $pending,
            'paid' => (int) $paid,
            'rewarded_referrals_count' => $rewardedCount,
        ]);
    }
}
