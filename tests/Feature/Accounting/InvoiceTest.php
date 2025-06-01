<?php

use App\Enums\Accounting\InvoiceStatus;
use App\Models\Accounting\Invoice;
use App\Utilities\Currency\CurrencyAccessor;

beforeEach(function () {
    $this->defaultCurrency = CurrencyAccessor::getDefaultCurrency();
    $this->withOfferings();
});

it('creates a basic invoice with line items and calculates totals correctly', function () {
    $invoice = Invoice::factory()
        ->withLineItems(2)
        ->create();

    $invoice->refresh();

    expect($invoice)
        ->hasLineItems()->toBeTrue()
        ->lineItems->count()->toBe(2)
        ->subtotal->toBeGreaterThan(0)
        ->total->toBeGreaterThan(0)
        ->amount_due->toBe($invoice->total);
});

describe('invoice approval', function () {
    beforeEach(function () {
        $this->invoice = Invoice::factory()
            ->withLineItems()
            ->approved()
            ->create();
    });

    test('approved invoices are marked as Unsent when not Overdue', function () {
        $this->invoice->update(['due_date' => now()->addDays(30)]);

        $this->invoice->refresh();

        expect($this->invoice)
            ->hasLineItems()->toBeTrue()
            ->status->toBe(InvoiceStatus::Unsent)
            ->wasApproved()->toBeTrue()
            ->approvalTransaction->not->toBeNull();
    });
});

it('creates sent invoices with line items and approval automatically', function () {
    $invoice = Invoice::factory()
        ->withLineItems()
        ->sent()
        ->create();

    $invoice->refresh();

    expect($invoice)
        ->hasLineItems()->toBeTrue()
        ->lineItems->count()->toBeGreaterThan(0)
        ->wasApproved()->toBeTrue()
        ->hasBeenSent()->toBeTrue()
        ->status->toBe(InvoiceStatus::Sent);
});

it('creates paid invoices with line items, approval, and payments automatically', function () {
    $invoice = Invoice::factory()
        ->withLineItems()
        ->paid()
        ->create();

    $invoice->refresh();

    expect($invoice)
        ->hasLineItems()->toBeTrue()
        ->lineItems->count()->toBeGreaterThan(0)
        ->wasApproved()->toBeTrue()
        ->hasBeenSent()->toBeTrue()
        ->hasPayments()->toBeTrue()
        ->isPaid()->toBeTrue()
        ->status->toBe(InvoiceStatus::Paid);
});

it('creates partial invoices with line items and partial payments automatically', function () {
    $invoice = Invoice::factory()
        ->withLineItems()
        ->partial()
        ->create();

    $invoice->refresh();

    expect($invoice)
        ->hasLineItems()->toBeTrue()
        ->lineItems->count()->toBeGreaterThan(0)
        ->wasApproved()->toBeTrue()
        ->hasBeenSent()->toBeTrue()
        ->hasPayments()->toBeTrue()
        ->status->toBeIn([InvoiceStatus::Partial, InvoiceStatus::Overdue])
        ->amount_paid->toBeGreaterThan(0)
        ->amount_paid->toBeLessThan($invoice->total);
});

it('creates overpaid invoices with line items and overpayments automatically', function () {
    $invoice = Invoice::factory()
        ->withLineItems()
        ->overpaid()
        ->create();

    $invoice->refresh();

    expect($invoice)
        ->hasLineItems()->toBeTrue()
        ->lineItems->count()->toBeGreaterThan(0)
        ->wasApproved()->toBeTrue()
        ->hasBeenSent()->toBeTrue()
        ->hasPayments()->toBeTrue()
        ->status->toBe(InvoiceStatus::Overpaid)
        ->amount_paid->toBeGreaterThan($invoice->total);
});

it('creates overdue invoices with line items and approval automatically', function () {
    $invoice = Invoice::factory()
        ->withLineItems()
        ->overdue()
        ->create();

    $invoice->refresh();

    expect($invoice)
        ->hasLineItems()->toBeTrue()
        ->lineItems->count()->toBeGreaterThan(0)
        ->wasApproved()->toBeTrue()
        ->status->toBe(InvoiceStatus::Overdue)
        ->due_date->toBeLessThan(now());
});

it('handles factory configure method without duplicate line items', function () {
    $invoice = Invoice::factory()
        ->withLineItems(2)
        ->create();

    $invoice->refresh();

    expect($invoice)
        ->hasLineItems()->toBeTrue()
        ->lineItems->count()->toBe(2)
        ->invoice_number->toStartWith('INV-')
        ->order_number->toStartWith('ORD-');
});
