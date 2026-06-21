export function fmtDate(val: string | null | undefined): string {
    if (!val) return '—'
    const d = new Date(val)
    if (isNaN(d.getTime())) return val
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

export function fmtDateTime(val: string | null | undefined): string {
    if (!val) return '—'
    const d = new Date(val)
    if (isNaN(d.getTime())) return val
    return d.toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
