<x-mail::message>
# {{ __('Booking confirmed') }}

{{ __('Great news — your booking has been confirmed. We look forward to seeing you on pick-up day.') }}

**{{ __('Reference') }}:** {{ $booking->reference() }}

**{{ __('Vehicle') }}:** {{ $booking->vehicle->name }}

**{{ __('Dates') }}:** {{ $booking->start_date->format('j M Y') }} — {{ $booking->end_date->format('j M Y') }}

**{{ __('Total') }}:** €{{ number_format((float) $booking->total_price, 2) }}

**{{ __('Status') }}:** {{ ucfirst($booking->status->value) }}

{{ __('Please have your driving licence and any required documents ready for pick-up. We will contact you shortly regarding the deposit.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
