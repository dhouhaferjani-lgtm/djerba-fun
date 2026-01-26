<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Listing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    private string $apiKey;

    private string $apiUrl;

    private bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.deepl.api_key', '');
        $this->apiUrl = config('services.deepl.api_url', 'https://api-free.deepl.com/v2/translate');
        $this->enabled = ! empty($this->apiKey);
    }

    /**
     * Check if the translation service is configured and enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Translate text from one language to another using DeepL API.
     *
     * @param  string  $text  The text to translate
     * @param  string  $targetLang  Target language code (EN, FR)
     * @param  string|null  $sourceLang  Source language code (auto-detect if null)
     * @return string|null Translated text or null on failure
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        if (empty($text) || ! $this->enabled) {
            return $text ?: null;
        }

        // Normalize language codes for DeepL (use EN-US for English)
        $targetLang = strtoupper($targetLang);

        if ($targetLang === 'EN') {
            $targetLang = 'EN-US';
        }

        // Cache key based on content hash
        $cacheKey = 'translation:' . md5($text . $targetLang . ($sourceLang ?? 'auto'));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($text, $targetLang, $sourceLang) {
            try {
                $params = [
                    'text' => [$text],
                    'target_lang' => $targetLang,
                ];

                if ($sourceLang) {
                    $params['source_lang'] = strtoupper($sourceLang);
                }

                $response = Http::withHeaders([
                    'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['translations'][0]['text'] ?? null;
                }

                Log::warning('DeepL translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Translation service error', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /**
     * Translate an array of texts in batch.
     *
     * @param  array<string>  $texts  Array of texts to translate
     * @param  string  $targetLang  Target language code
     * @param  string|null  $sourceLang  Source language code
     * @return array<string|null> Array of translated texts
     */
    public function translateBatch(array $texts, string $targetLang, ?string $sourceLang = null): array
    {
        if (empty($texts) || ! $this->enabled) {
            return $texts;
        }

        // Normalize language codes for DeepL
        $targetLang = strtoupper($targetLang);

        if ($targetLang === 'EN') {
            $targetLang = 'EN-US';
        }

        // Filter out empty texts and track their positions
        $nonEmptyTexts = [];
        $positions = [];

        foreach ($texts as $index => $text) {
            if (! empty($text)) {
                $nonEmptyTexts[] = $text;
                $positions[] = $index;
            }
        }

        if (empty($nonEmptyTexts)) {
            return $texts;
        }

        // Check cache first for each text
        $results = $texts;
        $textsToTranslate = [];
        $positionsToTranslate = [];

        foreach ($nonEmptyTexts as $i => $text) {
            $cacheKey = 'translation:' . md5($text . $targetLang . ($sourceLang ?? 'auto'));
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                $results[$positions[$i]] = $cached;
            } else {
                $textsToTranslate[] = $text;
                $positionsToTranslate[] = $positions[$i];
            }
        }

        // If all cached, return results
        if (empty($textsToTranslate)) {
            return $results;
        }

        try {
            $params = [
                'text' => $textsToTranslate,
                'target_lang' => $targetLang,
            ];

            if ($sourceLang) {
                $params['source_lang'] = strtoupper($sourceLang);
            }

            $response = Http::withHeaders([
                'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $translations = $data['translations'] ?? [];

                foreach ($translations as $i => $translation) {
                    $translatedText = $translation['text'] ?? null;
                    $position = $positionsToTranslate[$i];
                    $results[$position] = $translatedText;

                    // Cache the result
                    $cacheKey = 'translation:' . md5($textsToTranslate[$i] . $targetLang . ($sourceLang ?? 'auto'));
                    Cache::put($cacheKey, $translatedText, now()->addDays(30));
                }
            } else {
                Log::warning('DeepL batch translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Batch translation service error', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Translate all translatable fields of a listing from one language to another.
     *
     * @param  Listing  $listing  The listing to translate
     * @param  string  $targetLang  Target language (en or fr)
     * @return array{translated_fields: array<string>, skipped_fields: array<string>}
     */
    public function translateListing(Listing $listing, string $targetLang): array
    {
        $sourceLang = $targetLang === 'en' ? 'fr' : 'en';
        $translatedFields = [];
        $skippedFields = [];

        // Translatable string fields
        $stringFields = ['title', 'summary', 'description'];

        foreach ($stringFields as $field) {
            $sourceValue = $listing->getTranslation($field, $sourceLang);
            $targetValue = $listing->getTranslation($field, $targetLang);

            // Handle malformed arrays
            if (is_array($sourceValue)) {
                $sourceValue = $sourceValue[$sourceLang] ?? reset($sourceValue) ?: null;
            }

            if (is_array($targetValue)) {
                $targetValue = $targetValue[$targetLang] ?? reset($targetValue) ?: null;
            }

            // Only translate if source exists and target is empty
            if (! empty($sourceValue) && empty($targetValue)) {
                $translated = $this->translate($sourceValue, $targetLang, $sourceLang);

                if ($translated) {
                    $listing->setTranslation($field, $targetLang, $translated);
                    $translatedFields[] = $field;
                } else {
                    $skippedFields[] = $field;
                }
            } else {
                $skippedFields[] = $field;
            }
        }

        // Array fields with en/fr structure (highlights, included, not_included, requirements)
        $arrayFields = ['highlights', 'included', 'not_included', 'requirements'];

        foreach ($arrayFields as $field) {
            $value = $listing->{$field};

            if (! is_array($value) || empty($value)) {
                continue;
            }

            $modified = false;
            $textsToTranslate = [];
            $indices = [];

            // Collect texts that need translation
            foreach ($value as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $sourceText = $item[$sourceLang] ?? null;
                $targetText = $item[$targetLang] ?? null;

                if (! empty($sourceText) && empty($targetText)) {
                    $textsToTranslate[] = $sourceText;
                    $indices[] = $index;
                }
            }

            // Batch translate
            if (! empty($textsToTranslate)) {
                $translations = $this->translateBatch($textsToTranslate, $targetLang, $sourceLang);

                foreach ($translations as $i => $translatedText) {
                    if ($translatedText) {
                        $index = $indices[$i];
                        $value[$index][$targetLang] = $translatedText;
                        $modified = true;
                    }
                }

                if ($modified) {
                    $listing->{$field} = $value;
                    $translatedFields[] = $field;
                }
            }
        }

        return [
            'translated_fields' => $translatedFields,
            'skipped_fields' => $skippedFields,
        ];
    }

    /**
     * Get the remaining translation quota (characters) from DeepL.
     *
     * @return array{character_count: int, character_limit: int}|null
     */
    public function getUsage(): ?array
    {
        if (! $this->enabled) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
            ])->get(str_replace('/translate', '/usage', $this->apiUrl));

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get DeepL usage', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
