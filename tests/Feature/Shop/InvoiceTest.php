<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function paidOrderWithItems(?User $user = null, array $attributes = []): Order
{
    $order = Order::factory()->paid()->create([
        'user_id' => ($user ?? User::factory()->create(['business_name' => 'Green Leaf Traders']))->id,
        'subtotal' => 1000,
        'discount_amount' => 100,
        'tax_rate' => 12,
        'tax_inclusive' => true,
        'tax_amount' => 96.43,
        'total_amount' => 900,
        ...$attributes,
    ]);

    $product = Product::factory()->create(['name' => 'Herbal Hair Oil']);

    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => 'Herbal Hair Oil',
        'unit_price' => 500,
        'quantity' => 2,
        'line_total' => 1000,
    ]);

    return $order;
}

test('the owner can download the pdf invoice for a paid order', function () {
    $user = User::factory()->create();
    $order = paidOrderWithItems($user);

    $response = $this->actingAs($user)->get(route('orders.invoice', $order));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="'.$order->invoice_number.'.pdf"');

    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
});

test('another customer cannot download someone elses invoice', function () {
    $order = paidOrderWithItems();

    $this->actingAs(User::factory()->create())
        ->get(route('orders.invoice', $order))
        ->assertForbidden();
});

test('an admin can download any invoice', function () {
    $order = paidOrderWithItems();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('orders.invoice', $order))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('there is no invoice for an unpaid order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('orders.invoice', $order))->assertNotFound();
});

test('guests are sent to log in instead of getting an invoice', function () {
    $order = paidOrderWithItems();

    $this->get(route('orders.invoice', $order))->assertRedirect(route('login'));
});

test('the invoice shows the seller, buyer, items, discount and gst', function () {
    config([
        'invoice.company.name' => 'Pradiksha Herbal Pvt Ltd',
        'invoice.company.gstin' => '27ABCDE1234F1Z5',
        'invoice.tax.label' => 'GST',
    ]);

    $order = paidOrderWithItems();

    $html = view('invoices.invoice', [
        'order' => $order->load('items', 'user', 'package'),
        'company' => config('invoice.company'),
        'tax' => config('invoice.tax'),
        'footerNote' => 'Thank you.',
    ])->render();

    expect($html)
        ->toContain($order->invoice_number)
        ->toContain('Pradiksha Herbal Pvt Ltd')
        ->toContain('27ABCDE1234F1Z5')
        ->toContain('Green Leaf Traders')
        ->toContain('Herbal Hair Oil')
        ->toContain('₹1,000.00')
        ->toContain('-₹100.00')
        ->toContain('GST (12%, included in price)')
        ->toContain('₹96.43')
        ->toContain('₹900.00');
});

test('the invoice hides the gst and discount lines when there are none', function () {
    $order = paidOrderWithItems(attributes: ['discount_amount' => 0, 'tax_amount' => 0, 'tax_rate' => 0, 'total_amount' => 1000]);

    $html = view('invoices.invoice', [
        'order' => $order->load('items', 'user', 'package'),
        'company' => config('invoice.company'),
        'tax' => config('invoice.tax'),
        'footerNote' => '',
    ])->render();

    expect($html)->not->toContain('Discount')->not->toContain('GST (');
});

test('the confirmation email includes the order summary and the invoice pdf', function () {
    $order = paidOrderWithItems();

    $mail = new OrderConfirmationMail($order);

    $mail->assertHasSubject('Order #'.$order->id.' confirmed - Invoice '.$order->invoice_number);
    $mail->assertSeeInHtml('Herbal Hair Oil');
    $mail->assertSeeInHtml($order->invoice_number);
    $mail->assertSeeInHtml('₹900.00');

    $attachments = $mail->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]->as)->toBe($order->invoice_number.'.pdf')
        ->and($attachments[0]->mime)->toBe('application/pdf');
});

test('the order history lists invoices for paid orders and a pay now link for unpaid ones', function () {
    $user = User::factory()->create();
    $paid = paidOrderWithItems($user);
    $unpaid = Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee(route('orders.invoice', $paid))
        ->assertDontSee(route('orders.invoice', $unpaid))
        ->assertSee('Pay now');
});

test('the order page offers the invoice download once paid and a pay button before', function () {
    $user = User::factory()->create();
    $paid = paidOrderWithItems($user);
    $unpaid = Order::factory()->create(['user_id' => $user->id, 'total_amount' => 750]);

    $this->actingAs($user)
        ->get(route('orders.show', $paid))
        ->assertOk()
        ->assertSee('Download invoice')
        ->assertSee($paid->invoice_number)
        ->assertDontSee('Payment pending');

    $this->actingAs($user)
        ->get(route('orders.show', $unpaid))
        ->assertOk()
        ->assertSee('Payment pending')
        ->assertSee('Pay ₹750.00 now')
        ->assertDontSee('Download invoice');
});

test('the customer dashboard summarises paid orders and links to invoices', function () {
    $user = User::factory()->create();
    paidOrderWithItems($user);
    Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Orders placed', '1', 'Total spent', '₹900.00', 'Awaiting payment', '1'])
        ->assertSee('Invoice');
});

test('a customer cannot open another customers order', function () {
    $order = paidOrderWithItems();

    $this->actingAs(User::factory()->create())
        ->get(route('orders.show', $order))
        ->assertForbidden();
});

test('an admin marking an unpaid order as paid issues an invoice, emails the customer and notifies admins', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $customer->id, 'total_amount' => 600]);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('paymentStatus', PaymentStatus::Paid->value)
        ->call('updateOrder');

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_method)->toBe('manual')
        ->and($order->invoice_number)->not->toBeNull();

    Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->hasTo($customer->email));
    expect($admin->unreadNotifications)->toHaveCount(1);
});

test('the admin orders list can be filtered by payment status', function () {
    $admin = User::factory()->admin()->create();
    $paid = Order::factory()->paid()->create();
    $unpaid = Order::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.index')
        ->set('payment', 'paid')
        ->assertSee('#'.$paid->id)
        ->assertDontSee('#'.$unpaid->id);
});
