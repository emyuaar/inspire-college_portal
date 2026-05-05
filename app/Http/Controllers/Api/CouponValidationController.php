<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CouponValidationController extends Controller
{
    /**
     * Validate a coupon code for the current partner.
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->code;
        $partner = Auth::user();

        // Query the shared 'coupons' table (in mysql_website DB)
        // Since we are in Portal, we need to ensure we can access it.
        // Option 1: Use a Model with connection 'mysql_website' (like CRM does)
        // Option 2: Use DB::connection('mysql_website')

        $coupon = DB::connection('mysql_website')->table('coupons')
            ->where('code', $code)
            ->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Invalid coupon code.'], 404);
        }

        // 1. Active Check
        if (!$coupon->is_active) {
            return response()->json(['valid' => false, 'message' => 'This coupon is inactive.'], 422);
        }

        // 2. Expiry Check
        if ($coupon->expires_at && Carbon::parse($coupon->expires_at)->isPast()) {
            return response()->json(['valid' => false, 'message' => 'This coupon has expired.'], 422);
        }

        // 3. Max Redemptions Check
        if ($coupon->max_redemptions && $coupon->times_redeemed >= $coupon->max_redemptions) {
            return response()->json(['valid' => false, 'message' => 'This coupon has reached its usage limit.'], 422);
        }

        // 4. Connected Check (Partner Scoping)
        if ($coupon->connected && $coupon->connected_type === 'company') {
            // Check if Partner's Org ID matches Coupon's Connected ID
            if ($partner->org_id != $coupon->connected_id) {
                return response()->json(['valid' => false, 'message' => 'This coupon is not valid for your organization.'], 403);
            }
        }

        // 5. Success
        return response()->json([
            'valid' => true,
            'message' => 'Coupon applied successfully!',
            'coupon' => [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
                'stripe_promo_code_id' => $coupon->stripe_promo_code_id
            ]
        ]);
    }
}
