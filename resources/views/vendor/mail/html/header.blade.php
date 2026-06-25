@props(['url'])
<style>
    @font-face {
        font-family: 'Unbounded';
        src: url('{{ rtrim(config('app.url'), '/') . '/fonts/Unbounded.woff2' }}') format('woff2');
        font-weight: 700;
        font-style: normal;
        font-display: swap;
    }
</style>
<tr>
<td class="header">
<a
    href="{{ $url }}"
    style="display: inline-block; color: #178C69; font-size: 34px; line-height: 1; font-family: 'Unbounded', 'Avenir Next', 'Segoe UI', Helvetica, Arial, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; text-decoration: none;"
>
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
