<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rental Agreement — {{ $booking->reference() }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1a1a1a;
        }
        .page { padding: 36px 42px; }
        h1 { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        h2 {
            font-size: 13px;
            font-weight: bold;
            margin: 22px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #ccc;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .header { margin-bottom: 24px; }
        .header-meta { font-size: 10px; color: #555; margin-top: 6px; }
        .header-meta span { margin-right: 16px; }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #999;
            border-radius: 3px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .parties { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .parties td { width: 50%; vertical-align: top; padding: 0 12px 0 0; }
        .parties td:last-child { padding-right: 0; padding-left: 12px; }
        .party-box {
            border: 1px solid #ddd;
            padding: 10px 12px;
            min-height: 100px;
        }
        .party-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #666;
            margin-bottom: 6px;
        }
        .party-name { font-weight: bold; font-size: 12px; margin-bottom: 4px; }
        .muted { color: #555; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        table.data th {
            background: #f5f5f5;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .pricing-table { width: 100%; border-collapse: collapse; }
        .pricing-table td { padding: 5px 0; border-bottom: 1px solid #eee; }
        .pricing-table td:last-child { text-align: right; white-space: nowrap; }
        .pricing-total td {
            font-weight: bold;
            border-top: 2px solid #333;
            border-bottom: none;
            padding-top: 8px;
            font-size: 12px;
        }
        .deposit-row { margin-top: 10px; padding: 8px 10px; background: #f9f9f9; border: 1px solid #e5e5e5; }
        .terms { font-size: 10px; color: #333; }
        .terms ol { margin: 6px 0 0 18px; }
        .terms li { margin-bottom: 6px; }
        .disclaimer {
            margin-top: 12px;
            padding: 8px 10px;
            background: #fff8e6;
            border: 1px solid #e6d9a8;
            font-size: 9px;
            color: #665500;
        }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .signatures td { width: 50%; vertical-align: bottom; padding: 0 16px 0 0; }
        .signatures td:last-child { padding-right: 0; padding-left: 16px; }
        .sig-line { border-top: 1px solid #333; margin-top: 48px; padding-top: 6px; }
        .sig-label { font-size: 10px; font-weight: bold; }
        .sig-date { font-size: 9px; color: #666; margin-top: 4px; }
        .return-notice {
            margin-top: 8px;
            padding: 8px 10px;
            background: #f0f4f8;
            border-left: 3px solid #4a6fa5;
            font-size: 10px;
        }
        .operator-box {
            margin-top: 6px;
            padding: 8px 10px;
            background: #f5f5f5;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <h1>{{ $company['company_name'] }}</h1>
        <div style="font-size: 14px; font-weight: bold; margin-top: 2px;">Rental Agreement</div>
        <div class="header-meta">
            <span><strong>Reference:</strong> {{ $booking->reference() }}</span>
            <span><strong>Generated:</strong> {{ $generatedAt->format('j M Y, H:i') }}</span>
            <span class="status-badge">{{ ucfirst($booking->status->value) }}</span>
        </div>
    </div>

    <h2>Parties</h2>
    <table class="parties">
        <tr>
            <td>
                <div class="party-box">
                    <div class="party-label">Lessor (Company)</div>
                    <div class="party-name">{{ $company['company_legal_name'] }}</div>
                    <div class="muted">{{ $company['company_address'] }}</div>
                    <div class="muted" style="margin-top: 4px;">
                        Reg. No.: {{ $company['company_registration_number'] }}
                    </div>
                    <div class="muted">{{ $company['company_email'] }}</div>
                </div>
            </td>
            <td>
                <div class="party-box">
                    <div class="party-label">Lessee (Customer)</div>
                    <div class="party-name">{{ $customer->user->name }}</div>
                    <div class="muted">{{ $customer->user->email }}</div>
                    @if ($customer->driver_license_number)
                        <div class="muted" style="margin-top: 6px;">
                            <strong>Licence No.:</strong> {{ $customer->driver_license_number }}
                        </div>
                    @endif
                    @if ($customer->license_expiry)
                        <div class="muted">
                            <strong>Licence expiry:</strong> {{ $customer->license_expiry->format('j M Y') }}
                        </div>
                    @endif
                    @if ($customer->company_name)
                        <div class="muted" style="margin-top: 6px;">
                            <strong>Company:</strong> {{ $customer->company_name }}
                        </div>
                    @endif
                    @if ($customer->tax_number)
                        <div class="muted">
                            <strong>Tax No.:</strong> {{ $customer->tax_number }}
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <h2>Vehicle</h2>
    <table class="data">
        <tr>
            <th>Vehicle</th>
            <th>Category</th>
            <th>Year</th>
        </tr>
        <tr>
            <td>{{ $vehicle->name }}</td>
            <td>{{ $vehicle->category->name }}</td>
            <td>{{ $vehicle->year }}</td>
        </tr>
    </table>
    @if (count($specs) > 0)
        <table class="data" style="margin-top: 8px;">
            <tr>
                <th>Specification</th>
                <th>Value</th>
            </tr>
            @foreach ($specs as $spec)
                <tr>
                    <td>{{ $spec['label'] }}</td>
                    <td>{{ $spec['value'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Rental Period</h2>
    <table class="data">
        <tr>
            <th>Pick-up date (first rented day)</th>
            <th>Return date (vehicle must be returned by end of this date)</th>
        </tr>
        <tr>
            <td>{{ $booking->start_date->format('l, j F Y') }}</td>
            <td>{{ $booking->end_date->format('l, j F Y') }}</td>
        </tr>
    </table>
    <div class="return-notice">
        <strong>Return date notice:</strong>
        The return date above is the date by which the vehicle must be returned.
        It is the first day <em>not</em> included in the rental period (exclusive-end convention).
        Example: a rental from {{ $booking->start_date->format('j M') }} to {{ $booking->end_date->format('j M') }}
        covers the days from pick-up up to (but not including) the return date.
    </div>

    <h2>Pricing</h2>
    <table class="pricing-table">
        @forelse ($booking->pricing_breakdown ?? [] as $line)
            <tr>
                <td>{{ $line['label'] }}</td>
                <td>€{{ number_format((float) $line['amount'], 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="muted">No line-item breakdown recorded.</td>
            </tr>
        @endforelse
        <tr class="pricing-total">
            <td>Total rental price</td>
            <td>€{{ number_format((float) $booking->total_price, 2) }}</td>
        </tr>
    </table>
    <div class="deposit-row">
        <strong>Security deposit:</strong> €{{ number_format((float) $booking->deposit_amount, 2) }}
        —
        @if ($booking->deposit_paid_at)
            <strong>Paid</strong> on {{ $booking->deposit_paid_at->format('j M Y') }}
        @elseif (in_array($booking->status->value, ['pending', 'confirmed'], true))
            <strong>Due on confirmation</strong> (not charged until booking is confirmed)
        @else
            <strong>Outstanding</strong>
        @endif
    </div>

    @if ($booking->with_operator)
        <h2>Operator</h2>
        <div class="operator-box">
            This rental includes a qualified operator as agreed at the time of booking.
            The lessee acknowledges that machinery operation is subject to site safety requirements
            and that the operator's working hours follow the rental period stated above.
        </div>
    @endif

    <h2>Terms &amp; Conditions</h2>
    <div class="terms">
        <p>
            The following terms apply to this rental agreement. By accepting this booking,
            the lessee agrees to comply with all conditions set out below.
        </p>
        <ol>
            <li>
                <strong>Vehicle condition &amp; return:</strong>
                The vehicle must be returned in the same condition as at pick-up, subject to fair wear.
                Interior and exterior cleaning charges may apply if the vehicle is returned excessively dirty.
            </li>
            <li>
                <strong>Fuel policy:</strong>
                The vehicle must be returned with the same fuel level as at pick-up unless otherwise agreed.
                Refuelling charges apply at prevailing rates plus a service fee.
            </li>
            <li>
                <strong>Late return:</strong>
                Failure to return the vehicle by the agreed return date may result in additional daily charges
                and administrative fees. Extensions must be requested and approved in advance.
            </li>
            <li>
                <strong>Damage &amp; liability:</strong>
                The lessee is responsible for any damage, loss, or theft occurring during the rental period,
                subject to the security deposit and applicable insurance terms communicated at pick-up.
            </li>
            <li>
                <strong>Cancellation:</strong>
                Cancellations are subject to the company's cancellation policy in effect at the time of booking.
                Refunds, where applicable, may be partial depending on notice period and booking status.
            </li>
            <li>
                <strong>Permitted use:</strong>
                The vehicle may only be used by the named lessee (and authorised additional drivers if declared).
                Off-road use, racing, and illegal activities are prohibited unless explicitly agreed in writing.
            </li>
        </ol>
        <div class="disclaimer">
            <strong>Template notice:</strong>
            This document contains generic rental terms for demonstration purposes only.
            It does not constitute legal advice and has not been reviewed by a qualified legal professional.
            A production rental business should use terms drafted or reviewed by appropriate counsel.
        </div>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">
                    <div class="sig-label">Lessee (Customer)</div>
                    <div class="sig-date">Name: {{ $customer->user->name }}</div>
                    <div class="sig-date">Date: _________________________</div>
                </div>
            </td>
            <td>
                <div class="sig-line">
                    <div class="sig-label">Lessor (Authorised representative)</div>
                    <div class="sig-date">{{ $company['company_legal_name'] }}</div>
                    <div class="sig-date">Date: _________________________</div>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
