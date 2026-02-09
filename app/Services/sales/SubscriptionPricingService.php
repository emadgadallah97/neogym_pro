<?php

namespace App\Services\sales;

use App\Models\subscriptions\subscriptions_plan;
use App\Models\subscriptions\subscriptions_plan_branch_price;
use App\Models\subscriptions\subscriptions_plan_branch_coach_price;
use App\Models\general\TrainerSessionPricing;

class SubscriptionPricingService
{
    public function computePlanPricing(
        int $branchId,
        subscriptions_plan $plan,
        ?int $trainerId,
        bool $withTrainer
    ): array {
        $durationDays  = (int)($plan->duration_days ?? 0);
        $sessionsCount = (int)($plan->sessions_count ?? 0);

        $branchPrice = subscriptions_plan_branch_price::where('subscriptions_plan_id', $plan->id)
            ->where('branch_id', $branchId)
            ->first();

        $isPrivateCoach = $branchPrice ? (bool)$branchPrice->is_private_coach : false;

        // الافتراضي
        $pricePlan = $branchPrice ? (float)$branchPrice->price_without_trainer : 0.0;

        // لو العميل اختار withTrainer
        if ($withTrainer && $branchPrice) {

            // لو في سعر مخصص للمدرب في جدول coach prices
            if ($trainerId) {
                $coachPrice = subscriptions_plan_branch_coach_price::where('subscriptions_plan_id', $plan->id)
                    ->where('branch_id', $branchId)
                    ->where('employee_id', $trainerId)
                    ->first();

                if ($coachPrice) {
                    if ((bool)$coachPrice->is_included) {
                        $pricePlan = (float)$coachPrice->price;
                    } else {
                        $pricePlan = (float)$branchPrice->price_without_trainer + (float)$coachPrice->price;
                    }
                } else {
                    // fallback حسب وضع التسعير في branch price
                    if ($branchPrice->trainer_pricing_mode === 'uniform' && !is_null($branchPrice->trainer_uniform_price)) {
                        $pricePlan = (float)$branchPrice->trainer_uniform_price;
                    } elseif (!is_null($branchPrice->trainer_default_price)) {
                        $pricePlan = (float)$branchPrice->trainer_default_price;
                    }
                }
            } else {
                // لو لم يتم اختيار مدرب بعد: نعرض price_without_trainer كبداية (أو trainer_default إذا موجود)
                if (!is_null($branchPrice->trainer_default_price)) {
                    $pricePlan = (float)$branchPrice->trainer_default_price;
                }
            }
        }

        return [
            'price_plan'       => max(0, (float)$pricePlan),
            'duration_days'    => $durationDays,
            'sessions_count'   => $sessionsCount,
            'is_private_coach' => $isPrivateCoach,
        ];
    }

    public function computePtAddons(array $ptAddonsInput): array
    {
        $resultAddons = [];
        $total = 0.0;

        foreach ($ptAddonsInput as $row) {
            $trainerId = (int)($row['trainer_id'] ?? 0);
            $sessionsCount = (int)($row['sessions_count'] ?? 0);
            if ($trainerId <= 0 || $sessionsCount <= 0) {
                continue;
            }

            $pricing = TrainerSessionPricing::where('trainer_id', $trainerId)->orderByDesc('id')->first();
            $sessionPrice = $pricing ? (float)$pricing->session_price : 0.0;

            $rowTotal = $sessionPrice * $sessionsCount;
            $total += $rowTotal;

            $resultAddons[] = [
                'trainer_id'         => $trainerId,
                'session_price'      => $sessionPrice,
                'sessions_count'     => $sessionsCount,
                'sessions_remaining' => $sessionsCount,
                'total_amount'       => $rowTotal,
            ];
        }

        return [
            'addons'       => $resultAddons,
            'total_amount' => max(0, $total),
        ];
    }

    public function getTrainerSessionPrice(int $trainerId): float
    {
        $pricing = TrainerSessionPricing::where('trainer_id', $trainerId)->orderByDesc('id')->first();
        return $pricing ? (float)$pricing->session_price : 0.0;
    }
}
