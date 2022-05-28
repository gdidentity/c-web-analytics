export function formatNumber (number: number) {
    const formatter = Intl.NumberFormat('en', {
        notation: 'compact',
        minimumFractionDigits: number > 1000 ? 2 : 0,
        maximumFractionDigits: 2
    });
    return formatter.format(number)
}


export function formatDate (date: string, time: boolean = false) {
    return !time
    ? new Date(date).toLocaleDateString('en', { month: 'long', day: 'numeric' })
    : new Date(date).toLocaleTimeString('en', { hour: '2-digit', minute: '2-digit', hour12: false })
}
