{{-- $status: OrderStatus or PaymentStatus enum --}}
<span class="badge bg-{{ $status->color() }}">{{ $status->label() }}</span>
