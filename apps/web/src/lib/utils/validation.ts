/**
 * Validate international phone number format.
 * Accepts formats like: +216 52 665 202, +33612345678, 00216-52-665-202
 * Must contain between 8-15 digits and start with + or digit.
 */
export function isValidPhoneNumber(phone: string): boolean {
  // Remove all formatting characters (spaces, dashes, parentheses, dots)
  const cleaned = phone.replace(/[\s\-\(\)\.]/g, '');

  // Must start with + or digit
  if (!/^[\+\d]/.test(cleaned)) return false;

  // Must only contain digits after optional +
  if (!/^\+?\d+$/.test(cleaned)) return false;

  // Count actual digits (excluding the +)
  const digitCount = cleaned.replace(/\D/g, '').length;

  // Must have between 8 and 15 digits (international standard)
  return digitCount >= 8 && digitCount <= 15;
}
