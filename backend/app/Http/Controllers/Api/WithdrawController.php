<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesResourceQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawResource;
use App\Models\Setting;
use App\Models\Withdraw;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WithdrawController extends Controller
{
    use HandlesResourceQuery;

    /** Allowed transitions out of each state. */
    protected const FLOW = [
        'pending' => ['approved', 'rejected'],
        'approved' => ['paid', 'rejected'],
        'rejected' => [],
        'paid' => [],
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Withdraw::with(['requester', 'processor']);

        if ($type = $request->query('requester_type')) {
            $query->where('requester_type', Withdraw::REQUESTERS[$type] ?? $type);
        }

        $withdraws = $this->listing(
            $query,
            $request,
            searchable: ['account_name', 'account_number', 'bank_name'],
            filters: ['status' => 'status', 'method' => 'method', 'requester_id' => 'requester_id'],
            sortable: ['id', 'amount', 'status', 'created_at', 'processed_at'],
        );

        return WithdrawResource::collection($withdraws);
    }

    public function show(Withdraw $withdraw): JsonResponse
    {
        return (new WithdrawResource($withdraw->load(['requester', 'processor'])))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requester_type' => ['required', Rule::in(array_keys(Withdraw::REQUESTERS))],
            'requester_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['nullable', Rule::in(['bank', 'wallet', 'cash'])],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $model = Withdraw::REQUESTERS[$data['requester_type']];
        $requester = $model::find($data['requester_id']);
        if (! $requester) {
            $this->fail('requester_id', 'الحساب المطلوب غير موجود');
        }

        $min = (float) Setting::get('min_withdraw_amount');
        if ((float) $data['amount'] < $min) {
            $this->fail('amount', "الحد الأدنى للسحب هو {$min}");
        }
        if ((float) $data['amount'] > (float) $requester->balance) {
            $this->fail('amount', 'المبلغ المطلوب أكبر من الرصيد المتاح');
        }

        $withdraw = Withdraw::create([
            ...$data,
            'requester_type' => $model,
            'status' => 'pending',
        ]);

        return (new WithdrawResource($withdraw->load('requester')))->response()->setStatusCode(201);
    }

    /** PATCH /api/withdraws/{withdraw}/status — approve, reject or mark as paid. */
    public function updateStatus(Request $request, Withdraw $withdraw): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'paid'])],
            'admin_note' => ['nullable', 'string'],
        ]);

        if (! in_array($data['status'], self::FLOW[$withdraw->status] ?? [], true)) {
            $this->fail('status', 'لا يمكن الانتقال من الحالة الحالية إلى الحالة المطلوبة');
        }

        DB::transaction(function () use ($withdraw, $data, $request) {
            // the balance is only actually debited when the money goes out
            if ($data['status'] === 'paid') {
                $requester = $withdraw->requester()->lockForUpdate()->first();
                if (! $requester || (float) $requester->balance < (float) $withdraw->amount) {
                    $this->fail('status', 'رصيد الحساب لا يغطي المبلغ');
                }
                $requester->decrement('balance', (float) $withdraw->amount);
            }

            $withdraw->update([
                'status' => $data['status'],
                'admin_note' => $data['admin_note'] ?? $withdraw->admin_note,
                'processed_by' => $request->user()?->id,
                'processed_at' => now(),
            ]);
        });

        return (new WithdrawResource($withdraw->fresh()->load(['requester', 'processor'])))->response();
    }

    public function destroy(Withdraw $withdraw): JsonResponse
    {
        $this->guardDeletion();

        if ($withdraw->status === 'paid') {
            $this->fail('id', 'لا يمكن حذف طلب سحب مدفوع');
        }

        $withdraw->delete();

        return response()->json(['message' => 'تم نقل طلب السحب إلى سلّة المحذوفات']);
    }
}
