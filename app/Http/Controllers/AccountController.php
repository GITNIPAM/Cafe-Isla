<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\UserAddress;
use App\Services\DualChannelMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(protected DualChannelMailService $mailService) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = Order::with(['items.product', 'payment', 'delivery.courier', 'returnRequests'])
            ->where('user_id', $user->id)
            ->orderByDesc('placed_at')
            ->get();

        $addresses = $user->addresses()->orderByDesc('is_default')->get();
        $emailLogs = $user->emailLogs()->latest('sent_at')->limit(15)->get();

        return view('account.index', [
            'user' => $user,
            'orders' => $orders,
            'addresses' => $addresses,
            'emailLogs' => $emailLogs,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Profile details updated.');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'street_address' => ['required', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
            'delivery_zone' => ['required', 'in:local_express,provincial_parcel'],
        ]);

        $user = $request->user();
        $isFirst = $user->addresses()->count() === 0;

        UserAddress::create([
            ...$validated,
            'user_id' => $user->id,
            'is_default' => $isFirst,
        ]);

        return back()->with('success', 'Delivery address saved.');
    }

    public function deleteAddress(Request $request, UserAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        $address->delete();

        return back()->with('success', 'Saved address removed.');
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $order->canBeCancelledByCustomer()) {
            return back()->with('error', 'Only pending orders before barista preparation can be cancelled.');
        }

        $reason = trim((string) $request->input('reason', 'Customer cancelled prior to preparation'));

        DB::transaction(function () use ($order, $request, $reason) {
            foreach ($order->items as $item) {
                $item->variant()->increment('stock_qty', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);

            if ($order->payment && $order->payment->status === 'paid') {
                $order->payment->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);
            }

            ReturnRequest::create([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'request_type' => 'cancellation',
                'reason' => $reason,
                'status' => 'approved',
                'restock_applied' => true,
                'refund_amount' => $order->grand_total,
                'admin_remarks' => 'Auto-approved cancellation prior to bar preparation.',
            ]);
        });

        $this->mailService->sendOrderCancellation($order->fresh(), $reason);

        return back()->with('success', 'Order #'.$order->order_number.' cancelled cleanly. Check your Inbox for confirmation.');
    }

    public function requestReturn(Request $request, Order $order): RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        if (! $order->canRequestReturnByCustomer()) {
            return back()->with('error', 'Returns or spill refunds can only be filed for shipped or delivered orders.');
        }

        $validated = $request->validate([
            'order_item_id' => ['nullable', 'integer'],
            'request_type' => ['required', 'in:return,spill_refund'],
            'reason' => ['required', 'string', 'max:200'],
            'details' => ['nullable', 'string', 'max:600'],
        ]);

        $orderItem = null;
        if (! empty($validated['order_item_id'])) {
            $orderItem = $order->items()->findOrFail((int) $validated['order_item_id']);
        }

        $refundAmount = $orderItem ? (float) $orderItem->line_total : (float) $order->grand_total;

        ReturnRequest::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem?->id,
            'user_id' => $request->user()->id,
            'request_type' => $validated['request_type'],
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => 'pending',
            'restock_applied' => false,
            'refund_amount' => $refundAmount,
        ]);

        return back()->with('success', 'Return / refund request submitted for review.');
    }
}
