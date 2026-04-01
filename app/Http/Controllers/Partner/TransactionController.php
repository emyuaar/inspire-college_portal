<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Crm\Order;
use App\Models\Partner\PartnerLearnerInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TransactionController extends Controller
{
    /**
     * Display partner transaction history
     */
    public function index(Request $request)
    {
        $partner = Auth::user();
        $learnerIds = User::myLearners($partner->id)->pluck('id');

        $type = $request->get('type', 'all');
        $search = $request->get('search');

        // 1. Fetch Paid Installments (Deposits + Monthly)
        $installmentsQuery = PartnerLearnerInstallment::whereIn('learner_id', $learnerIds)
            ->where('status', 'paid')
            ->with(['learner', 'course']);

        if ($search) {
            $installmentsQuery->whereHas('learner', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('sur_name', 'like', "%{$search}%")
                  ->orWhere('email_address', 'like', "%{$search}%");
            });
        }

        if ($type === 'deposits') {
            $installmentsQuery->where('installment_no', 0);
        } elseif ($type === 'installments') {
            $installmentsQuery->where('installment_no', '>', 0);
        } else {
            // For 'all' or other types, we might want to avoid double counting 
            // if we use Orders as the primary source for Deposits.
            // However, to keep it simple, let's just make sure we capture everything.
            // If an installment is NO > 0, it's a monthly payment.
            // If NO = 0, it's a deposit.
        }

        $installments = ($type === 'full') ? collect() : $installmentsQuery->get()->map(function($i) {
            return (object)[
                'id' => $i->id,
                'source' => 'installment',
                'learner' => $i->learner,
                'course_title' => $i->course->title ?? 'Course',
                'amount' => $i->paid_amount,
                'date' => $i->paid_at ?? $i->updated_at,
                'type' => ($i->installment_no == 0 ? 'Deposit' : 'Installment #' . $i->installment_no),
                'reference' => $i->payment_reference ?? 'MNL-' . $i->id,
                'status' => 'Paid',
                'learner_id' => $i->learner_id
            ];
        });

        // 2. Fetch All Paid Orders (Covers Full Payments and Deposit Orders)
        $ordersQuery = Order::whereIn('learner_id', $learnerIds)
            ->where('status_id', 1)
            ->with(['learner', 'enrolment.course']);

        if ($search) {
            $ordersQuery->whereHas('learner', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('sur_name', 'like', "%{$search}%")
                  ->orWhere('email_address', 'like', "%{$search}%");
            });
        }

        if ($type === 'deposits') {
            $ordersQuery->where('payment_mode', 'installment');
        } elseif ($type === 'installments') {
            $ordersQuery->whereRaw('0=1'); // Installments are in the other table
        } elseif ($type === 'full') {
            $ordersQuery->where('payment_mode', 'full');
        }

        $orders = ($type === 'installments') ? collect() : $ordersQuery->get()->map(function($o) {
            $isDeposit = ($o->payment_mode === 'installment');
            return (object)[
                'id' => $o->id,
                'source' => 'order',
                'learner' => $o->learner,
                'course_title' => $o->enrolment->course->title ?? 'Course',
                'amount' => $o->amount,
                'date' => $o->updated_at ?? $o->created_at,
                'type' => $isDeposit ? 'Deposit' : 'Full Payment',
                'reference' => $o->stripe_payment_id ?? 'ORD-' . $o->id,
                'status' => 'Paid',
                'learner_id' => $o->learner_id
            ];
        });

        // Combine and Sort
        // To avoid showing BOTH the Order (deposit) and Installment #0 (deposit)
        // we'll filter out installment #0 if it matches an order ID already in the list.
        $orderIds = $orders->pluck('id')->toArray();
        $filteredInstallments = $installments->filter(function($i) use ($orderIds) {
            if (str_contains($i->type, 'Deposit')) {
                // If we also found this as an Order, skip it here to avoid duplication
                // Actually, checking by 'order_id' is safer if available
                return false; // Let's prefer the Order entry for Deposits for now as it's the "Source"
            }
            return true;
        });

        $allTransactions = $filteredInstallments->concat($orders)->sortByDesc('date');

        // Pagination
        $perPage = 15;
        $page = $request->get('page', 1);
        $paginatedItems = $allTransactions->slice(($page - 1) * $perPage, $perPage)->values();
        
        $transactions = new LengthAwarePaginator(
            $paginatedItems,
            $allTransactions->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('partner.transactions.index', compact('transactions', 'type', 'search'));
    }
}
