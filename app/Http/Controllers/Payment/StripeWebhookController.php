<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Crm\Order;
use App\Models\Crm\OrderInstallment;
use App\Models\Crm\Payment; // Verify this model exists in Portal or create it if missing (It was in CRM only analysis)
// Wait, I didn't create Payment model in Portal. Analysis said Payment model is in CRM. 
// I need access to it in Portal's namespace to write to 'payments' table.
// I will add it to imports assuming I create it or just use DB facade for simplicity if strict model missing?
// ACTUALLY, I should create App\Models\Crm\Payment in Portal first. I forgot that in Phase 1/2.
// I will create it now in this same step if possible or just use DB facade to insert into mysql_crm.payments?
// Better to use Model. I'll stick to DB facade for the Payment record insertion to avoid another file creation step if strictness not required, OR create the model.
// Requirement 5 says "Reuse existing... Do not introduce new frameworks...". 
// I will create the Payment model in Portal for consistency.
// BUT for now, I'll assume I can just use DB::connection('mysql_crm')->table('payments')->insert(...) for the audit log.
use App\Models\Crm\Enrolment;
use App\Models\Crm\EnrolmentStatus;
use App\Services\MicrosoftGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    protected $paymentService;

    public function __construct(\App\Services\PaymentProcessingService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function handle(Request $request, MicrosoftGraphService $graphService)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $metadata = $session->metadata ?? [];

            if (isset($metadata->type) && ($metadata->type == 'course_purchase' || $metadata->type == 'order_payment')) {
                // Delegate to Service
                try {
                    $this->paymentService->processOrderPayment($session, $metadata);
                } catch (\Exception $e) {
                     return response()->json(['error' => $e->getMessage()], 500);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
