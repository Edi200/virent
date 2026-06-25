export function useBookingDisplay() {
    const eurFormatter = new Intl.NumberFormat('en-EU', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const dateFormatter = new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    function formatEur(amount: string | number): string {
        return eurFormatter.format(Number(amount));
    }

    function formatDate(date: string): string {
        return dateFormatter.format(new Date(`${date}T00:00:00`));
    }

    function statusLabel(status: string): string {
        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    function statusBadgeClass(status: string): string {
        switch (status) {
            case 'pending':
                return 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950/50 dark:text-amber-300';
            case 'confirmed':
                return 'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-700 dark:bg-blue-950/50 dark:text-blue-300';
            case 'active':
                return 'border-transparent bg-primary text-primary-foreground';
            case 'completed':
                return 'border-green-300 bg-green-50 text-green-800 dark:border-green-700 dark:bg-green-950/50 dark:text-green-300';
            case 'cancelled':
                return 'border-transparent bg-destructive text-white';
            default:
                return '';
        }
    }

    return {
        formatEur,
        formatDate,
        statusLabel,
        statusBadgeClass,
    };
}
