<x-mail::message>
# {{ __('Booking request received') }}

{{ __('Thank you for your booking request. We have received your reservation and will be in touch to confirm it.') }}

**{{ __('Reference') }}:** VR-{{ str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT) }}

**{{ __('Vehicle') }}:** {{ $booking->vehicle->name }}

**{{ __('Dates') }}:** {{ $booking->start_date->format('j M Y') }} — {{ $booking->end_date->format('j M Y') }}

**{{ __('Total') }}:** €{{ number_format((float) $booking->total_price, 2) }}

**{{ __('Status') }}:** {{ ucfirst($booking->status->value) }}

{{ __('No payment has been taken at this stage. We will contact you to confirm your booking.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
