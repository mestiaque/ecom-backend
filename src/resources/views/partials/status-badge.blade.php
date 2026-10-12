{{-- $status: OrderStatus or PaymentStatus enum --}}
<span class="badge bg-{{ $status->color() }}-subtle text-{{ $status->color() }}">{{ $status->label() }}</span>
