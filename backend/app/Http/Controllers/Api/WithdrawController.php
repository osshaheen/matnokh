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

    // Payment LOG — the customer pays the merchant directly to the merchant's own
    // account; the platform only monitors and records. No approve/reject workflow.

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

        // Just a record for documentation — marked recorded, no approval.
        $withdraw = Withdraw::create([
            ...$data,
            'requester_type' => $model,
            'status' => 'recorded',
        ]);

        return (new WithdrawResource($withdraw->load('requester')))->response()->setStatusCode(201);
    }

    public function destroy(Withdraw $withdraw): JsonResponse
    {
        $this->guardDeletion();

        $withdraw->delete();

        return response()->json(['message' => 'تم حذف السجل']);
    }
}
