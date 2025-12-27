#!/usr/bin/env node

/**
 * Translation Coverage Checker
 *
 * Compares English and French translation files to identify:
 * - Missing French translations
 * - Extra French keys (potentially deprecated)
 * - Coverage percentage
 */

const fs = require('fs');
const path = require('path');

const enPath = path.join(__dirname, '../apps/web/messages/en.json');
const frPath = path.join(__dirname, '../apps/web/messages/fr.json');

// Read and parse JSON files
const enMessages = JSON.parse(fs.readFileSync(enPath, 'utf-8'));
const frMessages = JSON.parse(fs.readFileSync(frPath, 'utf-8'));

/**
 * Recursively flatten nested object into dot-notation keys
 * @param {object} obj - Object to flatten
 * @param {string} prefix - Current key prefix
 * @returns {string[]} Array of flattened keys
 */
function flattenKeys(obj, prefix = '') {
  let keys = [];

  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key;

    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
      // Recursively flatten nested objects
      keys = keys.concat(flattenKeys(value, fullKey));
    } else {
      // Leaf node - add the key
      keys.push(fullKey);
    }
  }

  return keys;
}

// Flatten both translation files
const enKeys = new Set(flattenKeys(enMessages));
const frKeys = new Set(flattenKeys(frMessages));

// Find missing and extra keys
const missingInFr = [...enKeys].filter(k => !frKeys.has(k));
const extraInFr = [...frKeys].filter(k => !enKeys.has(k));

// Calculate coverage
const coverage = ((frKeys.size / enKeys.size) * 100).toFixed(1);

// Output report
console.log('='.repeat(70));
console.log('Translation Coverage Report');
console.log('='.repeat(70));
console.log();
console.log(`Total English keys: ${enKeys.size}`);
console.log(`Total French keys: ${frKeys.size}`);
console.log(`Coverage: ${coverage}%`);
console.log();

if (missingInFr.length > 0) {
  console.log(`❌ Missing in French (${missingInFr.length}):`);
  console.log('─'.repeat(70));

  // Group by section for better readability
  const grouped = {};
  missingInFr.forEach(key => {
    const section = key.split('.')[0];
    if (!grouped[section]) grouped[section] = [];
    grouped[section].push(key);
  });

  Object.entries(grouped).sort().forEach(([section, keys]) => {
    console.log(`\n  [${section}] (${keys.length} missing):`);
    keys.forEach(k => console.log(`    - ${k}`));
  });

  console.log();
  console.log('─'.repeat(70));
}

if (extraInFr.length > 0) {
  console.log();
  console.log(`⚠️  Extra in French (${extraInFr.length}) - may be deprecated:`);
  console.log('─'.repeat(70));
  extraInFr.forEach(k => console.log(`  - ${k}`));
  console.log();
}

if (missingInFr.length === 0) {
  console.log();
  console.log('✅ All English keys have French translations!');
  console.log();
}

console.log('='.repeat(70));

// Exit with error code if there are missing translations
process.exit(missingInFr.length > 0 ? 1 : 0);
