<?php

namespace App\Services;

use App\Models\Website\Course;

class PricingService
{
    /**
     * Calculate pricing for a course, including active promotions and installment plans.
     * 
     * @param Course $course
     * @return array
     */
    public function getCoursePricing(Course $course): array
    {
        // 1. Get Base Attributes
        $regularPrice = (float) $course->regular_price;
        $salePrice = (float) $course->sale_price;
        
        // 2. Check for Active Promotion
        // We load the relationship if not loaded? 
        // Better to rely on the caller eager loading or just lazy load here.
        $activePromo = $course->activePromotion;
        $activeCoursePromo = $course->activeCoursePromotion; // The pivot with override values

        $isPromo = false;
        $finalFullPrice = $regularPrice; // Default
        $discountPercent = 0;
        $promoName = null;

        // Pricing Logic
        if ($activePromo) {
            $isPromo = true;
            $discountPercent = $activePromo->discount_percent;
            $promoName = $activePromo->name ?? 'Special Offer';
            
            // Rule: Discount applied to Regular Price
            if ($discountPercent > 0) {
                $discountAmount = $regularPrice * ($discountPercent / 100);
                $finalFullPrice = $regularPrice - $discountAmount;
            }
        } elseif ($salePrice > 0 && $salePrice < $regularPrice) {
            // Fallback to simple Sale Price if no promo active
            $finalFullPrice = $salePrice;
        }

        // 3. Installment Plan Logic
        // Check for overrides in activeCoursePromotion pivot
        $deposit = $course->deposit;
        $monthlyAmount = $course->monthly_installment;
        $months = $course->number_of_months;

        if ($activeCoursePromo) {
            // If overrides exist, they take precedence
            if (!is_null($activeCoursePromo->deposit)) {
                $deposit = $activeCoursePromo->deposit;
            }
            if (!is_null($activeCoursePromo->monthly_installment)) {
                $monthlyAmount = $activeCoursePromo->monthly_installment;
            }
            if (!is_null($activeCoursePromo->number_of_months)) {
                $months = $activeCoursePromo->number_of_months;
            }
        }

        $deposit = (float) $deposit;
        $monthlyAmount = (float) $monthlyAmount;
        $months = (int) $months;

        $totalInstallmentPrice = $deposit + ($monthlyAmount * $months);

        return [
            'course_id' => $course->id,
            'title' => $course->title,
            'is_promo' => $isPromo,
            'promo_name' => $promoName,
            'discount_percent' => $discountPercent,
            'regular_price' => $regularPrice,
            'final_full_price' => round($finalFullPrice, 2),
            'installment_plan' => [
                'available' => ($months > 0),
                'deposit' => round($deposit, 2),
                'monthly_amount' => round($monthlyAmount, 2),
                'months' => $months,
                'total_price' => round($totalInstallmentPrice, 2)
            ]
        ];
    }
}
