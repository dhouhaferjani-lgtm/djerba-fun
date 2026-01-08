/**
 * Resolves a translatable field based on the current locale.
 * Handles both object format {en: "...", fr: "..."} and plain strings.
 */
export type TranslatableField = string | { [locale: string]: string } | null | undefined;

export function resolveTranslation(
  field: TranslatableField,
  locale: string,
  fallbackLocale = 'en'
): string {
  if (!field) {
    return '';
  }

  // If it's already a string, return it
  if (typeof field === 'string') {
    return field;
  }

  // Handle empty arrays (from corrupted data)
  if (Array.isArray(field) && field.length === 0) {
    return '';
  }

  // If it's an object, try to get the translation for the current locale
  if (typeof field === 'object') {
    return field[locale] || field[fallbackLocale] || Object.values(field)[0] || '';
  }

  return '';
}

/**
 * Creates a translator function bound to a specific locale.
 * Useful for components that need to translate multiple fields.
 */
export function createTranslator(locale: string, fallbackLocale = 'en') {
  return (field: TranslatableField): string => resolveTranslation(field, locale, fallbackLocale);
}
