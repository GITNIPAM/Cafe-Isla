<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserAddress;
use App\Services\OrderReturnService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(protected OrderReturnService $orderReturns) {}

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
            'postal_code' => ['required', 'regex:/^\d{4}$/'],
            'delivery_zone' => ['required', 'in:local_express,provincial_parcel'],
        ], [
            'postal_code.regex' => 'Enter a valid 4-digit Philippine postal code.',
        ]);

        if ($validated['delivery_zone'] === 'local_express') {
            $localMap = [
                'dasmarinas|cavite' => '4114',
                'san juan|la union' => '2514',
            ];
            $key = strtolower(str_replace('ñ', 'n', trim($validated['city']))).'|'.strtolower(trim($validated['province']));
            if (! isset($localMap[$key]) || $localMap[$key] !== trim($validated['postal_code'])) {
                return back()->withErrors([
                    'city' => 'Local Express is only available in Dasmariñas, Cavite (4114) and San Juan, La Union (2514).',
                ])->withInput();
            }
        }

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

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:200']]);
        try {
            $this->orderReturns->cancel($order, $validated['reason'] ?? 'Customer cancelled prior to preparation');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order #'.$order->order_number.' cancelled. Check your Inbox for confirmation.');
    }

    public function requestReturn(Request $request, Order $order): RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'order_item_id' => ['nullable', 'integer'],
            'request_type' => ['required', 'in:return,spill_refund'],
            'reason' => ['required', 'string', 'max:200'],
            'details' => ['nullable', 'string', 'max:600'],
        ]);

        $submitted = DB::transaction(function () use ($order, $validated) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if (! $order->canRequestReturnByCustomer()) {
                return false;
            }

            $orderItem = isset($validated['order_item_id'])
                ? $order->items()->findOrFail($validated['order_item_id'])
                : null;
            $order->returnRequests()->create([
                'order_item_id' => $orderItem?->id,
                'user_id' => $order->user_id,
                'request_type' => $validated['request_type'],
                'reason' => $validated['reason'],
                'details' => $validated['details'] ?? null,
                'status' => 'pending',
                'restock_applied' => false,
                'refund_amount' => $order->returnCredit($orderItem),
            ]);

            return true;
        }, 3);

        if (! $submitted) {
            return back()->with('error', 'A shipped or delivered order can have one return request.');
        }

        return back()->with('success', 'Return / refund request submitted for review.');
    }
}
