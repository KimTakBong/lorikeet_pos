/**
 * Format number as currency (Indonesian format)
 * @param {number|string} value - The number to format
 * @returns {string} Formatted number with thousand separators
 */
export function formatCurrency(value) {
  if (value === null || value === undefined || value === '') return '';
  const num = typeof value === 'string' ? value.replace(/[^0-9]/g, '') : value;
  return new Intl.NumberFormat('id-ID').format(num);
}

/**
 * Parse formatted currency to raw number
 * @param {string} value - The formatted number
 * @returns {number} Raw number without separators
 */
export function parseCurrency(value) {
  if (value === null || value === undefined || value === '') return 0;
  return parseInt(value.replace(/[^0-9]/g, ''), 10) || 0;
}

/**
 * Format currency for display (with Rp prefix)
 * @param {number|string} value - The number to format
 * @returns {string} Formatted currency with Rp prefix
 */
export function formatCurrencyDisplay(value) {
  const formatted = formatCurrency(value);
  if (!formatted) return 'Rp 0';
  return `Rp ${formatted}`;
}

export default { formatCurrency, parseCurrency, formatCurrencyDisplay };
