#!/usr/bin/env tsx
/**
 * Import Translations from CSV
 *
 * Imports translations from CSV file back into French and English JSON files.
 * Preserves key ordering from original files.
 *
 * Usage: pnpm i18n:import
 *
 * Input: apps/web/translations.csv
 */

import { readFileSync, writeFileSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';
import { parseCSV, setNestedValue, reorderToMatch, type NestedObj } from './translation-utils.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const messagesDir = resolve(__dirname, '../messages');
const inputPath = resolve(__dirname, '../translations.csv');

// Read the CSV
let csvContent: string;
try {
  csvContent = readFileSync(inputPath, 'utf-8');
} catch (error) {
  console.error(`\n❌ Error: Could not read ${inputPath}`);
  console.error('   Run "pnpm i18n:export" first to generate the CSV file.\n');
  process.exit(1);
}

const rows = parseCSV(csvContent);

if (rows.length < 2) {
  console.error('CSV file is empty or has no data rows');
  process.exit(1);
}

// Parse header to find column indices
const header = rows[0];
const keyIdx = header.indexOf('key');
const enIdx = header.indexOf('english');
const frIdx = header.indexOf('french');

if (keyIdx === -1 || enIdx === -1 || frIdx === -1) {
  console.error('CSV must have columns: key, english, french');
  console.error('Found columns:', header);
  process.exit(1);
}

// Build nested objects from CSV
const enObj: NestedObj = {};
const frObj: NestedObj = {};

let importedCount = 0;
for (let r = 1; r < rows.length; r++) {
  const row = rows[r];
  const key = row[keyIdx];
  if (!key) continue;

  setNestedValue(enObj, key, row[enIdx] ?? '');
  setNestedValue(frObj, key, row[frIdx] ?? '');
  importedCount++;
}

// Read originals to preserve key ordering
let origEn: NestedObj = {};
let origFr: NestedObj = {};

try {
  origEn = JSON.parse(readFileSync(resolve(messagesDir, 'en.json'), 'utf-8'));
  origFr = JSON.parse(readFileSync(resolve(messagesDir, 'fr.json'), 'utf-8'));
} catch {
  console.log('Note: Original files not found, creating new ones.');
}

// Reorder to match original key order
const orderedEn = reorderToMatch(enObj, origEn);
const orderedFr = reorderToMatch(frObj, origFr);

// Write updated JSON files
writeFileSync(resolve(messagesDir, 'en.json'), JSON.stringify(orderedEn, null, 2) + '\n', 'utf-8');
writeFileSync(resolve(messagesDir, 'fr.json'), JSON.stringify(orderedFr, null, 2) + '\n', 'utf-8');

console.log(`\n✅ Imported ${importedCount} translation keys`);
console.log(`   Updated: messages/en.json, messages/fr.json\n`);

// Run validation
console.log('Running translation validation...\n');
