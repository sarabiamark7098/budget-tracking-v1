/**
 * Format a date value as "Month DD, YYYY" (e.g. "January 01, 1970").
 *
 * Accepts:
 *   - "YYYY-MM-DD"               → date-only string from the API
 *   - "YYYY-MM-DDTHH:mm:ss..."   → ISO timestamp string
 *   - null / undefined / ''      → returns '—'
 *
 * The date part is parsed as UTC so that midnight UTC dates are never shifted
 * to the previous day by the browser's local timezone offset.
 */
export function formatDate(value) {
    if (!value) return '—';

    // Take only the first 10 characters ("YYYY-MM-DD") from any date/datetime string
    const str = String(value).substring(0, 10);
    const [y, m, d] = str.split('-').map(Number);

    if (!y || !m || !d) return String(value);

    const date = new Date(Date.UTC(y, m - 1, d));

    return date.toLocaleDateString('en-US', {
        month: 'long',
        day:   '2-digit',
        year:  'numeric',
        timeZone: 'UTC',
    });
}
