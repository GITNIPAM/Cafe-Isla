<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Mail;

class DualChannelMailService
{
    public function sendOrderConfirmation(Order $order): EmailLog
    {
        $itemsRows = '';
        foreach ($order->items as $item) {
            $itemsRows .= '<tr>'
                .'<td style="padding:8px 0;border-bottom:1px solid #D8CEBC;">'.e($item->product_name_snapshot).' <span style="color:#6E6256;">('.e($item->variant_label_snapshot).')</span></td>'
                .'<td style="padding:8px 0;border-bottom:1px solid #D8CEBC;text-align:center;">x'.$item->quantity.'</td>'
                .'<td style="padding:8px 0;border-bottom:1px solid #D8CEBC;text-align:right;font-family:Courier New,monospace;">₱'.number_format((float) $item->line_total, 2).'</td>'
                .'</tr>';
        }

        $subject = 'Café Isla · Order Confirmation #'.$order->order_number;
        $html = $this->wrapTemplate(
            'Salamat, '.e($order->billing_name).' — Your Archipelago Order is Confirmed',
            '<p>We received your order <strong>#'.e($order->order_number).'</strong> at the Café Isla bar and queued it for preparation.</p>'
            .'<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">'.$itemsRows.'</table>'
            .'<p style="font-family:Courier New,monospace;font-size:13px;">'
            .'Subtotal: ₱'.number_format((float) $order->subtotal, 2).' &middot; '
            .'Discount: -₱'.number_format((float) $order->discount_amount, 2).' &middot; '
            .'Delivery: ₱'.number_format((float) $order->delivery_fee, 2).'<br>'
            .'<strong>Grand Total: ₱'.number_format((float) $order->grand_total, 2).'</strong>'
            .'</p>'
            .'<p><strong>Delivering to:</strong> '.e($order->shipping_address_line.', '.$order->shipping_city.', '.$order->shipping_province).'</p>'
        );

        return $this->dispatchAndLog($order->billing_email, $order->user_id, $order->id, 'order_confirmation', $subject, $html);
    }

    public function sendShippingUpdate(Order $order): EmailLog
    {
        $delivery = $order->delivery;
        $courierName = $delivery?->courier?->name ?? $delivery?->carrier_name ?? 'Isla Express Rider';
        $tracking = $delivery?->tracking_number ?? 'PENDING';

        $subject = 'Café Isla · Shipping Update for #'.$order->order_number.' ('.ucwords(str_replace('_', ' ', (string) $order->status)).')';
        $html = $this->wrapTemplate(
            'Order #'.e($order->order_number).' Status: '.ucwords(str_replace('_', ' ', (string) $order->status)),
            '<p>Your Café Isla parcel/pour is moving through our archipelago dispatch line.</p>'
            .'<p><strong>Courier:</strong> '.e($courierName).'<br>'
            .'<strong>Tracking Number:</strong> <code>'.e($tracking).'</code><br>'
            .'<strong>Delivery Status:</strong> '.e(ucwords(str_replace('_', ' ', (string) ($delivery?->status ?? 'assigned')))).'</p>'
            .($delivery?->proof_notes ? '<p><strong>Rider Notes:</strong> '.e($delivery->proof_notes).'</p>' : '')
        );

        return $this->dispatchAndLog($order->billing_email, $order->user_id, $order->id, 'shipping_update', $subject, $html);
    }

    public function sendOrderCancellation(Order $order, string $reason = 'Cancelled prior to preparation'): EmailLog
    {
        $payment = $order->payment;
        $paymentNote = match (true) {
            (float) $payment?->refunded_amount > 0 => '<p>Simulated refund recorded: ₱'.number_format((float) $payment->refunded_amount, 2).'.</p>',
            $payment?->method === 'cod' && $payment->status === 'pending' => '<p>There is no Cash on Delivery payment due.</p>',
            default => '',
        };
        $subject = 'Café Isla · Order #'.$order->order_number.' Cancelled';
        $html = $this->wrapTemplate(
            'Order #'.e($order->order_number).' Cancelled',
            '<p>Your order <strong>#'.e($order->order_number).'</strong> has been cancelled before barista prep.</p>'
            .'<p><strong>Reason:</strong> '.e($reason).'</p>'
            .$paymentNote
        );

        return $this->dispatchAndLog($order->billing_email, $order->user_id, $order->id, 'order_cancellation', $subject, $html);
    }

    public function sendReturnUpdate(ReturnRequest $returnRequest): EmailLog
    {
        $order = $returnRequest->order;
        $statusLabel = ucfirst($returnRequest->status);
        $restockLine = $returnRequest->restock_applied ? '<p>Eligible merch stock was restored to inventory.</p>' : '';
        $paymentLine = match (true) {
            (float) $returnRequest->cod_adjustment > 0 => '<p><strong>Cash on Delivery reduction:</strong> ₱'.number_format((float) $returnRequest->cod_adjustment, 2).'</p>'
                .'<p><strong>Remaining balance:</strong> ₱'.number_format($order->payment?->amountToCollect() ?? 0, 2).'</p>',
            (float) $returnRequest->refund_amount > 0 => '<p><strong>Simulated refund:</strong> ₱'.number_format((float) $returnRequest->refund_amount, 2).'</p>',
            default => '',
        };

        $subject = 'Café Isla · Return Request '.$statusLabel.' (#'.$order->order_number.')';
        $html = $this->wrapTemplate(
            'Return & Refund Update — '.$statusLabel,
            '<p>Your <strong>'.e(str_replace('_', ' ', $returnRequest->request_type)).'</strong> request for Order <strong>#'.e($order->order_number).'</strong> is now <strong>'.e($statusLabel).'</strong>.</p>'
            .$paymentLine
            .$restockLine
            .($returnRequest->admin_remarks ? '<p><strong>Café Team Note:</strong> '.e($returnRequest->admin_remarks).'</p>' : '')
        );

        return $this->dispatchAndLog($order->billing_email, $order->user_id, $order->id, 'return_update', $subject, $html);
    }

    protected function dispatchAndLog(
        string $recipient,
        ?int $userId,
        ?int $orderId,
        string $eventType,
        string $subject,
        string $bodyHtml
    ): EmailLog {
        $smtpStatus = 'logged-only';

        try {
            Mail::html($bodyHtml, function ($message) use ($recipient, $subject) {
                $message->to($recipient)->subject($subject);
            });
            $mailer = (string) config('mail.default', 'log');
            $smtpStatus = $mailer === 'log' ? 'delivered (dual-log)' : 'smtp-sent + logged';
        } catch (\Throwable $e) {
            $smtpStatus = 'logged-fallback ('.substr($e->getMessage(), 0, 32).')';
        }

        return EmailLog::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'recipient_email' => $recipient,
            'event_type' => $eventType,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'smtp_status' => $smtpStatus,
            'sent_at' => now(),
        ]);
    }

    protected function wrapTemplate(string $headline, string $innerHtml): string
    {
        return '<div style="font-family:Georgia,serif;background:#F6F1E6;color:#261D18;padding:24px;border:1px solid #261D18;max-width:600px;">'
            .'<div style="border-bottom:1px solid #261D18;padding-bottom:12px;margin-bottom:16px;">'
            .'<span style="font-family:Arial,sans-serif;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#C4553A;">Café Isla &middot; Slow sips from the archipelago</span>'
            .'<h2 style="margin:6px 0 0 0;font-style:italic;font-weight:400;color:#1C3127;">'.$headline.'</h2>'
            .'</div>'
            .$innerHtml
            .'<div style="margin-top:20px;padding-top:12px;border-top:1px solid #D8CEBC;font-size:12px;color:#6E6256;">Café Isla Coastal Wellness &amp; Merch &middot; Urbiztondo &middot; Dasmariñas &middot; Manila</div>'
            .'</div>';
    }
}
