<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Resources\Merchant\WalletTransactionResource;
use App\Models\Setting;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /** GET /api/merchant/wallet — balance + transaction log. */
    public function index(Request $request): JsonResponse
    {
        $m = $request->attributes->get('merchant');
        $txns = $m->walletTransactions()->with('order')->latest('id')->paginate(20);
        $pending = $m->walletTransactions()->where('type', 'withdrawal')->where('status', 'pending')->exists();

        return response()->json([
            'balance' => (float) $m->balance,
            'min_withdraw' => (float) Setting::get('min_withdraw_amount'),
            'has_pending_withdraw' => $pending,
            'transactions' => WalletTransactionResource::collection($txns->items()),
            'meta' => ['total' => $txns->total(), 'per_page' => $txns->perPage(), 'current_page' => $txns->currentPage(), 'last_page' => $txns->lastPage()],
        ]);
    }

    /** POST /api/merchant/withdraws — request a payout (one pending at a time). */
    public function requestWithdraw(Request $request): JsonResponse
    {
        $m = $request->attributes->get('merchant');
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['nullable', 'in:bank,wallet,cash'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($m->walletTransactions()->where('type', 'withdrawal')->where('status', 'pending')->exists()) {
            return response()->json(['message' => 'لديك طلب سحب معلّق — انتظر اعتماده أولاً'], 422);
        }
        $min = (float) Setting::get('min_withdraw_amount');
        if ((float) $data['amount'] < $min) {
            return response()->json(['message' => "الحد الأدنى للسحب هو {$min}"], 422);
        }
        if ((float) $data['amount'] > (float) $m->balance) {
            return response()->json(['message' => 'المبلغ أكبر من الرصيد المتاح'], 422);
        }

        $txn = $m->walletTransactions()->create([
            'type' => 'withdrawal',
            'amount' => $data['amount'],
            'status' => 'pending',
            'method' => $data['method'] ?? 'bank',
            'note' => $data['note'] ?? null,
        ]);

        return (new WalletTransactionResource($txn))->additional(['message' => 'تم إرسال طلب السحب'])->response()->setStatusCode(201);
    }
}
