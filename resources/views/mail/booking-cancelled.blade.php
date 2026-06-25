<x-mail::message>
# {{ __('Booking cancelled') }}

{{ __('Your booking has been cancelled. If you have any questions, please get in touch.') }}

**{{ __('Reference') }}:** {{ $booking->reference() }}

**{{ __('Vehicle') }}:** {{ $booking->vehicle->name }}

**{{ __('Dates') }}:** {{ $booking->start_date->format('j M Y') }} — {{ $booking->end_date->format('j M Y') }}

**{{ __('Total') }}:** €{{ number_format((float) $booking->total_price, 2) }}

**{{ __('Status') }}:** {{ ucfirst($booking->status->value) }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
