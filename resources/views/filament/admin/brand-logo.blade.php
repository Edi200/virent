<style>
    @font-face {
        font-family: 'Unbounded';
        src: url('{{ asset('fonts/Unbounded.woff2') }}') format('woff2');
        font-weight: 200 900;
        font-style: normal;
        font-display: swap;
    }

    .virent-filament-logo-wrap {
        display: flex;
        align-items: center;
        height: 2.25rem;
    }

    .virent-filament-wordmark {
        font-family: 'Unbounded', ui-sans-serif, system-ui, sans-serif;
        font-weight: 700;
        font-size: 2rem;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: var(--primary-600);
        line-height: 1;
        display: flex;
        align-items: center;
        height: 100%;
    }
</style>

<span class="virent-filament-logo-wrap">
    <span class="virent-filament-wordmark">VIRENT</span>
</span>
