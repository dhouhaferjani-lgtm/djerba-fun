#!/usr/bin/env tsx
/**
 * Export Translations to CSV
 *
 * Exports French and English translation JSON files to a single CSV file
 * that can be edited in Excel or Google Sheets.
 *
 * Usage: pnpm i18n:export
 *
 * Output: apps/web/translations.csv
 */

import { readFileSync, writeFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import { flatten, escapeCSV, CSV_BOM, type NestedObj } from './translation-utils.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const messagesDir = resolve(__dirname, '../messages');
const outputPath = resolve(__dirname, '../translations.csv');

function loadLocale(locale: string): Record<string, string> {
  const filePath = resolve(messagesDir, `${locale}.json`);
  try {
    const raw = readFileSync(filePath, 'utf-8');
    return flatten(JSON.parse(raw) as NestedObj);
  } catch (error) {
    console.error(`Error loading ${locale}.json:`, error);
    process.exit(1);
  }
}

// Load both locales
const en = loadLocale('en');
const fr = loadLocale('fr');

// Union of all keys across locales to avoid dropping locale-specific entries
const allKeys = [...new Set([...Object.keys(en), ...Object.keys(fr)])].sort();

// Build CSV rows
const rows = [['key', 'english', 'french'].map(escapeCSV).join(',')];

for (const key of allKeys) {
  rows.push([key, en[key] ?? '', fr[key] ?? ''].map(escapeCSV).join(','));
}

// Write CSV with BOM for Excel UTF-8 compatibility
writeFileSync(outputPath, CSV_BOM + rows.join('\n'), 'utf-8');

console.log(`\n✅ Exported ${allKeys.length} translation keys to:`);
console.log(`   ${outputPath}\n`);

// Show summary by namespace
const namespaces = new Map<string, number>();
for (const key of allKeys) {
  const ns = key.split('.')[0];
  namespaces.set(ns, (namespaces.get(ns) || 0) + 1);
}

console.log('Namespaces:');
for (const [ns, count] of [...namespaces.entries()].sort()) {
  console.log(`  - ${ns}: ${count} keys`);
}
console.log();
