/**
 * Translation Utilities for CSV Import/Export
 *
 * Shared utilities for converting between nested JSON and flat CSV formats.
 * Used by both export-translations-csv.ts and import-translations-csv.ts
 */

export type NestedObj = { [key: string]: string | NestedObj };

/**
 * Flatten a nested object into dot-notation key-value pairs.
 *
 * Example:
 *   { common: { search: "Search" } }
 *   → { "common.search": "Search" }
 */
export function flatten(obj: NestedObj, prefix = ''): Record<string, string> {
  const result: Record<string, string> = {};

  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key;
    if (typeof value === 'object' && value !== null) {
      Object.assign(result, flatten(value as NestedObj, fullKey));
    } else {
      result[fullKey] = String(value);
    }
  }

  return result;
}

/**
 * Set a value in a nested object using dot-notation key.
 *
 * Example:
 *   setNestedValue({}, "common.search", "Search")
 *   → { common: { search: "Search" } }
 */
export function setNestedValue(obj: NestedObj, dotKey: string, value: string): void {
  const parts = dotKey.split('.');
  let current = obj;

  for (let i = 0; i < parts.length - 1; i++) {
    const part = parts[i];
    if (!(part in current) || typeof current[part] !== 'object') {
      current[part] = {};
    }
    current = current[part] as NestedObj;
  }

  current[parts[parts.length - 1]] = value;
}

/**
 * Unflatten a flat object with dot-notation keys into a nested object.
 *
 * Example:
 *   { "common.search": "Search" }
 *   → { common: { search: "Search" } }
 */
export function unflatten(flat: Record<string, string>): NestedObj {
  const result: NestedObj = {};

  for (const [key, value] of Object.entries(flat)) {
    setNestedValue(result, key, value);
  }

  return result;
}

/**
 * Escape a value for CSV format.
 * Wraps in quotes if contains comma, quote, or newline.
 */
export function escapeCSV(value: string): string {
  if (value.includes(',') || value.includes('"') || value.includes('\n') || value.includes('\r')) {
    return `"${value.replace(/"/g, '""')}"`;
  }
  return value;
}

/**
 * Parse CSV content into rows of values.
 * Handles quoted fields, escaped quotes, and BOM.
 */
export function parseCSV(content: string): string[][] {
  const rows: string[][] = [];
  let i = 0;

  // Skip BOM if present
  if (content.charCodeAt(0) === 0xfeff) i = 1;

  while (i < content.length) {
    const row: string[] = [];

    while (i < content.length) {
      let value = '';

      if (content[i] === '"') {
        // Quoted field
        i++; // skip opening quote
        while (i < content.length) {
          if (content[i] === '"') {
            if (content[i + 1] === '"') {
              value += '"';
              i += 2;
            } else {
              i++; // skip closing quote
              break;
            }
          } else {
            value += content[i];
            i++;
          }
        }
      } else {
        // Unquoted field
        while (
          i < content.length &&
          content[i] !== ',' &&
          content[i] !== '\n' &&
          content[i] !== '\r'
        ) {
          value += content[i];
          i++;
        }
      }

      row.push(value);

      if (i < content.length && content[i] === ',') {
        i++; // skip comma, continue to next field
      } else {
        // End of row
        break;
      }
    }

    // Skip line endings
    if (i < content.length && content[i] === '\r') i++;
    if (i < content.length && content[i] === '\n') i++;

    if (row.length > 0 && row.some((cell) => cell.length > 0)) {
      rows.push(row);
    }
  }

  return rows;
}

/**
 * Reorder keys in target to match the order in reference.
 * New keys not in reference are added at the end.
 */
export function reorderToMatch(target: NestedObj, reference: NestedObj): NestedObj {
  const result: NestedObj = {};

  // First, add keys in the order they appear in the reference
  for (const key of Object.keys(reference)) {
    if (key in target) {
      const targetVal = target[key];
      const refVal = reference[key];
      if (
        typeof targetVal === 'object' &&
        targetVal !== null &&
        typeof refVal === 'object' &&
        refVal !== null
      ) {
        result[key] = reorderToMatch(targetVal as NestedObj, refVal as NestedObj);
      } else {
        result[key] = targetVal;
      }
    }
  }

  // Then add any new keys not in the reference
  for (const key of Object.keys(target)) {
    if (!(key in result)) {
      result[key] = target[key];
    }
  }

  return result;
}

/**
 * BOM (Byte Order Mark) for UTF-8 CSV files.
 * Helps Excel recognize UTF-8 encoding.
 */
export const CSV_BOM = '\uFEFF';
