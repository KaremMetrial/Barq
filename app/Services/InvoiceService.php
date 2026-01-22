<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Seller;
use LaravelDaily\Invoices\Invoice;
use Modules\Order\Models\Order;

class InvoiceService
{
    /**
     * Generate and return a streamed PDF invoice for the given order
     *
     * @param Order $order
     * @param string $format
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function generateInvoice(Order $order, string $format = 'pdf')
    {
        switch ($format) {
            case 'pdf':
                return $this->generatePdfInvoice($order);
            case 'html':
                return $this->generateHtmlInvoice($order);
            case 'json':
                return $this->generateJsonInvoice($order);
            default:
                return $this->generatePdfInvoice($order);
        }
    }

    /**
     * Generate PDF invoice with streaming
     *
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    protected function generatePdfInvoice(Order $order)
    {
        // Create seller (store) information
        $seller = new Seller([
            'name' => $order->store->name,
            'address' => $order->store->address_place,
            'code' => $order->store->reference_code ?? '',
            'vat' => $order->store->tax_number ?? '',
            'phone' => $order->store->phone,
            'custom_fields' => [
                'Store ID' => $order->store->id,
                'Store Email' => $order->store->email ?? '',
            ],
        ]);

        // Create buyer (customer) information
        $buyer = new Buyer([
            'name' => $order->user->name ?? 'Guest Customer',
            'address' => $order->deliveryAddress?->getFullAddressAttribute() ?? 'N/A',
            'phone' => $order->user->phone ?? '',
            'custom_fields' => [
                'Order Number' => $order->order_number,
                'Order Date' => $order->created_at->format('Y-m-d H:i:s'),
                'Payment Method' => $order->paymentMethod?->name ?? 'Cash',
                'Payment Status' => ucfirst($order->payment_status->value ?? 'unpaid'),
            ],
        ]);

        // Create invoice items from order items
        $items = [];
        foreach ($order->items as $item) {
            $description = $item->product->name;
            
            // Add option value if exists
            if ($item->productOptionValue) {
                $description .= ' - ' . $item->productOptionValue->name;
            }
            
            // Add add-ons if exists
            if ($item->addOns->isNotEmpty()) {
                foreach ($item->addOns as $addon) {
                    $description .= PHP_EOL . '+ ' . $addon->name . ' (x' . $addon->pivot->quantity . ')';
                }
            }
            
            // Add note if exists
            if ($item->note) {
                $description .= PHP_EOL . 'Note: ' . $item->note;
            }

            $invoiceItem = InvoiceItem::make($description)
                ->description($item->product->name)
                ->pricePerUnit($item->total_price / $item->quantity)
                ->quantity($item->quantity)
                ->discount($item->discount_amount ?? 0);

            $items[] = $invoiceItem;
        }

        // Create the invoice
        $invoice = Invoice::make()
            ->seller($seller)
            ->buyer($buyer)
            ->series('ORD')
            ->sequence($order->id)
            ->serialNumberFormat('{SERIES}-{SEQUENCE}')
            ->currencySymbol($order->store->currency_symbol ?? 'EGP')
            ->currencyCode('EGP')
            ->currencyFormat('{VALUE} {SYMBOL}')
            ->currencyThousandsSeparator(',')
            ->currencyDecimalPoint('.')
            ->filename("Invoice-{$order->order_number}")
            ->date($order->created_at)
            ->dateFormat('Y-m-d')
            ->payUntilDays(7)
            ->taxRate(0)
            ->addItems($items);

        // Add additional charges
        if ($order->delivery_fee > 0) {
            $invoice->addItem(
                InvoiceItem::make('Delivery Fee')
                    ->description('Delivery charge for order')
                    ->pricePerUnit($order->delivery_fee)
                    ->quantity(1)
            );
        }

        if ($order->service_fee > 0) {
            $invoice->addItem(
                InvoiceItem::make('Service Fee')
                    ->description('Service charge')
                    ->pricePerUnit($order->service_fee)
                    ->quantity(1)
            );
        }

        if ($order->tax_amount > 0) {
            $invoice->addItem(
                InvoiceItem::make('Tax')
                    ->description('Tax amount')
                    ->pricePerUnit($order->tax_amount)
                    ->quantity(1)
            );
        }

        if ($order->tip_amount > 0) {
            $invoice->addItem(
                InvoiceItem::make('Tip')
                    ->description('Customer tip')
                    ->pricePerUnit($order->tip_amount)
                    ->quantity(1)
            );
        }

        // Add discount if exists
        if ($order->discount_amount > 0) {
            $invoice->addItem(
                InvoiceItem::make('Discount')
                    ->description('Order discount')
                    ->pricePerUnit(-$order->discount_amount)
                    ->quantity(1)
            );
        }

        // Set totals
        $invoice->totalAmount(
            $order->total_amount - $order->discount_amount + 
            $order->delivery_fee + $order->service_fee + 
            $order->tax_amount + $order->tip_amount
        );

        // Return streamed PDF response
        return $invoice->stream();
    }

    /**
     * Generate HTML invoice view
     *
     * @param Order $order
     * @return \Illuminate\View\View
     */
    protected function generateHtmlInvoice(Order $order)
    {
        return view('order::invoice', compact('order'));
    }

    /**
     * Generate JSON invoice data
     *
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    protected function generateJsonInvoice(Order $order)
    {
        return response()->json([
            'order' => new \Modules\Order\Http\Resources\OrderResource($order)
        ]);
    }
}
