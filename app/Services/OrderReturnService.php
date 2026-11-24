<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class OrderReturnService
{
    public function __construct(private DualChannelMailService $mailService) {}

    public function cancel(Order $order, string $reason, ?User $admin = null): void
    {
        $order = DB::transaction(function () use ($order, $reason, $admin) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if (! $order->canBeCancelledByCustomer() && ! ($admin && $order->status === 'confirmed')) {
                throw new DomainException('This order can only be cancelled before preparation starts.');
            }

            $restocked = false;
            foreach ($order->items()->orderBy('product_variant_id')->get() as $item) {
                if ($item->variant()->increment('stock_qty', $item->quantity) > 0) {
                    $restocked = true;
                }
            }

            $order->update(['status' => 'cancelled']);
            $order->delivery?->update(['status' => 'failed', 'courier_user_id' => null]);
            $order->returnRequests()->create([
                'user_id' => $order->user_id,
                'request_type' => 'cancellation',
                'reason' => $reason,
                'status' => 'approved',
                'restock_applied' => $restocked,
                ...$this->applyCredit($order, (float) $order->grand_total),
                'processed_by' => $admin?->id,
                'admin_remarks' => $admin ? 'Cancelled by the café before preparation.' : 'Auto-approved before preparation.',
            ]);

            return $order;
        }, 3);

        $this->mailService->sendOrderCancellation($order, $reason);
    }

    public function process(ReturnRequest $return, User $admin, string $decision, ?string $remarks): void
    {
        $return = DB::transaction(function () use ($return, $admin, $decision, $remarks) {
            $order = Order::lockForUpdate()->findOrFail($return->order_id);
            $return = $order->returnRequests()->lockForUpdate()->findOrFail($return->id);
            if ($return->status !== 'pending') {
                throw new DomainException('This return request has already been processed.');
            }

            $restocked = false;
            $credit = ['refund_amount' => 0, 'cod_adjustment' => 0];
            if ($decision === 'approved') {
                if (! $order->isReturnable()) {
                    throw new DomainException('This order is no longer eligible for a return.');
                }

                $items = $order->items()
                    ->when($return->order_item_id, fn ($query, $id) => $query->whereKey($id))
                    ->where('is_perishable', false)->orderBy('product_variant_id')->get();
                foreach ($items as $item) {
                    if ($item->variant()->increment('stock_qty', $item->quantity) > 0) {
                        $restocked = true;
                    }
                }

                $credit = $this->applyCredit($order, min(
                    (float) $return->refund_amount,
                    $order->returnCredit($return->orderItem),
                ));

                if (! $return->order_item_id) {
                    if ($order->status === 'shipped') {
                        $order->delivery?->update(['status' => 'failed']);
                    }
                    $order->update(['status' => 'returned']);
                }
            }

            $return->update([
                'status' => $decision,
                'restock_applied' => $restocked,
                ...$credit,
                'processed_by' => $admin->id,
                'admin_remarks' => $remarks,
            ]);

            return $return->setRelation('order', $order);
        }, 3);

        $this->mailService->sendReturnUpdate($return);
    }

    private function applyCredit(Order $order, float $amount): array
    {
        return $order->payment?->applyReturnCredit($amount) ?? ['refund_amount' => 0, 'cod_adjustment' => 0];
    }
}
