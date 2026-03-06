/**
 * Tests for Translation Utilities
 *
 * Run with: npx tsx scripts/translation-utils.test.ts
 */

import assert from 'assert';
import {
  flatten,
  unflatten,
  setNestedValue,
  escapeCSV,
  parseCSV,
  reorderToMatch,
  type NestedObj,
} from './translation-utils.js';

let passed = 0;
let failed = 0;

function test(name: string, fn: () => void): void {
  try {
    fn();
    console.log(`  ✓ ${name}`);
    passed++;
  } catch (error) {
    console.log(`  ✗ ${name}`);
    console.log(`    ${error}`);
    failed++;
  }
}

function deepEqual(a: unknown, b: unknown): boolean {
  return JSON.stringify(a) === JSON.stringify(b);
}

console.log('\n=== flatten() ===\n');

test('flattens simple nested object', () => {
  const input = { common: { search: 'Search' } };
  const expected = { 'common.search': 'Search' };
  assert.deepStrictEqual(flatten(input), expected);
});

test('flattens deeply nested object', () => {
  const input = { a: { b: { c: { d: 'value' } } } };
  const expected = { 'a.b.c.d': 'value' };
  assert.deepStrictEqual(flatten(input), expected);
});

test('handles multiple keys at same level', () => {
  const input = { common: { search: 'Search', book: 'Book' } };
  const result = flatten(input);
  assert.strictEqual(result['common.search'], 'Search');
  assert.strictEqual(result['common.book'], 'Book');
});

test('handles top-level keys', () => {
  const input = { title: 'Hello' };
  const expected = { title: 'Hello' };
  assert.deepStrictEqual(flatten(input), expected);
});

test('handles empty object', () => {
  assert.deepStrictEqual(flatten({}), {});
});

test('converts numbers to strings', () => {
  const input = { count: 42 as unknown as string };
  const result = flatten(input);
  assert.strictEqual(result['count'], '42');
});

console.log('\n=== unflatten() ===\n');

test('unflattens simple dot-notation', () => {
  const input = { 'common.search': 'Search' };
  const expected = { common: { search: 'Search' } };
  assert.deepStrictEqual(unflatten(input), expected);
});

test('unflattens deeply nested keys', () => {
  const input = { 'a.b.c.d': 'value' };
  const expected = { a: { b: { c: { d: 'value' } } } };
  assert.deepStrictEqual(unflatten(input), expected);
});

test('handles multiple keys in same namespace', () => {
  const input = { 'common.search': 'Search', 'common.book': 'Book' };
  const expected = { common: { search: 'Search', book: 'Book' } };
  assert.deepStrictEqual(unflatten(input), expected);
});

test('handles top-level keys', () => {
  const input = { title: 'Hello' };
  const expected = { title: 'Hello' };
  assert.deepStrictEqual(unflatten(input), expected);
});

console.log('\n=== round-trip (flatten → unflatten) ===\n');

test('round-trip preserves simple object', () => {
  const original = { common: { search: 'Search' } };
  const result = unflatten(flatten(original));
  assert.deepStrictEqual(result, original);
});

test('round-trip preserves complex nested object', () => {
  const original = {
    common: { search: 'Search', book_now: 'Book Now' },
    home: { hero_title: 'Welcome', hero_subtitle: 'Explore' },
    navigation: { home: 'Home', tours: 'Tours' },
  };
  const result = unflatten(flatten(original));
  assert.deepStrictEqual(result, original);
});

console.log('\n=== escapeCSV() ===\n');

test('returns simple string unchanged', () => {
  assert.strictEqual(escapeCSV('hello'), 'hello');
});

test('escapes string with comma', () => {
  assert.strictEqual(escapeCSV('hello, world'), '"hello, world"');
});

test('escapes string with quotes', () => {
  assert.strictEqual(escapeCSV('say "hello"'), '"say ""hello"""');
});

test('escapes string with newline', () => {
  assert.strictEqual(escapeCSV('line1\nline2'), '"line1\nline2"');
});

test('escapes string with comma and quotes', () => {
  assert.strictEqual(escapeCSV('Hello, "world"'), '"Hello, ""world"""');
});

console.log('\n=== parseCSV() ===\n');

test('parses simple CSV', () => {
  const csv = 'key,english,french\ncommon.search,Search,Rechercher';
  const expected = [
    ['key', 'english', 'french'],
    ['common.search', 'Search', 'Rechercher'],
  ];
  assert.deepStrictEqual(parseCSV(csv), expected);
});

test('parses CSV with quoted fields', () => {
  const csv = 'key,english,french\ncommon.message,"Hello, world","Bonjour, monde"';
  const result = parseCSV(csv);
  assert.strictEqual(result[1][1], 'Hello, world');
  assert.strictEqual(result[1][2], 'Bonjour, monde');
});

test('parses CSV with escaped quotes', () => {
  const csv = 'key,english\ncommon.quote,"Say ""hello"""';
  const result = parseCSV(csv);
  assert.strictEqual(result[1][1], 'Say "hello"');
});

test('handles BOM at start', () => {
  const csv = '\uFEFFkey,english\ntest,value';
  const result = parseCSV(csv);
  assert.strictEqual(result[0][0], 'key');
});

test('handles Windows line endings (CRLF)', () => {
  const csv = 'key,english\r\ntest,value';
  const result = parseCSV(csv);
  assert.strictEqual(result.length, 2);
});

test('handles multiline values in quotes', () => {
  const csv = 'key,english\ntest,"line1\nline2"';
  const result = parseCSV(csv);
  assert.strictEqual(result[1][1], 'line1\nline2');
});

console.log('\n=== CSV round-trip (escape → parse) ===\n');

test('round-trip preserves values with special characters', () => {
  const values = ['Hello, world', 'Say "hello"', 'Line1\nLine2', 'Normal'];
  const escaped = values.map(escapeCSV);
  const csvLine = escaped.join(',');
  const parsed = parseCSV(csvLine)[0];
  assert.deepStrictEqual(parsed, values);
});

console.log('\n=== reorderToMatch() ===\n');

test('preserves key order from reference', () => {
  const target = { b: 'B', a: 'A', c: 'C' };
  const reference = { a: 'x', b: 'x', c: 'x' };
  const result = reorderToMatch(target, reference);
  const keys = Object.keys(result);
  assert.deepStrictEqual(keys, ['a', 'b', 'c']);
});

test('adds new keys at the end', () => {
  const target = { a: 'A', newKey: 'New', b: 'B' };
  const reference = { a: 'x', b: 'x' };
  const result = reorderToMatch(target, reference);
  const keys = Object.keys(result);
  assert.deepStrictEqual(keys, ['a', 'b', 'newKey']);
});

test('handles nested objects', () => {
  const target = { common: { b: 'B', a: 'A' } };
  const reference = { common: { a: 'x', b: 'x' } };
  const result = reorderToMatch(target, reference);
  const nestedKeys = Object.keys(result.common as NestedObj);
  assert.deepStrictEqual(nestedKeys, ['a', 'b']);
});

console.log('\n=== setNestedValue() ===\n');

test('sets value at nested path', () => {
  const obj: NestedObj = {};
  setNestedValue(obj, 'a.b.c', 'value');
  assert.strictEqual(((obj.a as NestedObj).b as NestedObj).c, 'value');
});

test('creates intermediate objects', () => {
  const obj: NestedObj = {};
  setNestedValue(obj, 'deep.nested.path', 'value');
  assert.strictEqual(((obj.deep as NestedObj).nested as NestedObj).path, 'value');
});

test('sets top-level value', () => {
  const obj: NestedObj = {};
  setNestedValue(obj, 'key', 'value');
  assert.strictEqual(obj.key, 'value');
});

// Summary
console.log('\n' + '='.repeat(50));
console.log(`\nResults: ${passed} passed, ${failed} failed\n`);

if (failed > 0) {
  process.exit(1);
}
