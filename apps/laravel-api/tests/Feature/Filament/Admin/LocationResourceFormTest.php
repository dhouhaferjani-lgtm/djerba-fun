<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\LocationResource;
use App\Models\Location;
use App\Models\User;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component as LivewireComponent;
use Tests\TestCase;

/**
 * Regression tests for the Admin Locations form.
 *
 * Original ticket: clients reported a raw 500 Server Error when creating or
 * editing a Location in the Filament admin panel. Three independent root
 * causes all surfaced as the same error and are pinned here:
 *   1. Filament Google Maps Map widget called MapsHelper::mapsKey() (strict
 *      `: string` return type) which returned null when GOOGLE_MAPS_API_KEY
 *      was empty — TypeError on form load.
 *   2. country was a free-text TextInput (default 'Tunisia', maxLength 100)
 *      against a varchar(2) DB column — SQLSTATE 22001 on save.
 *   3. city was missing ->required() despite being NOT NULL in DB —
 *      SQLSTATE 23502 on save with blank city.
 *
 * Pinned contract:
 *   1. Every NOT NULL column in `locations` is marked required in the form.
 *   2. Country is a Select limited to ISO-3166 alpha-2 codes.
 *   3. Create / Edit pages render OK whether or not the Google Maps key is
 *      configured.
 *   4. The model-level happy path keeps writing valid rows.
 *   5. Postgres still rejects NULL `city` at the DB layer (canary against
 *      anyone making the column nullable in a future migration).
 */
class LocationResourceFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);
    }

    /**
     * Scenario 1 — Bug fix: `city` is marked required in the form schema.
     *
     * Given the Admin Location form is built,
     * When the `city` field is inspected,
     * Then it must report `isRequired() === true`.
     *
     * Direct guard against the reported 500: a blank `city` must be caught
     * by Filament validation before it ever hits the Postgres NOT NULL
     * constraint on `locations.city`.
     */
    public function test_city_field_is_required_in_form_schema(): void
    {
        $field = $this->getFormField('city');

        $this->assertNotNull($field, 'The Admin Location form must include a `city` field.');
        $this->assertTrue(
            $field->isRequired(),
            'The `city` field must be marked ->required() because the DB column is NOT NULL.',
        );
    }

    /**
     * Scenario 2 — Every NOT NULL DB column is marked required in the form.
     *
     * Given the Admin Location form is built,
     * When required fields are collected,
     * Then the set must include every column declared NOT NULL in the
     * `locations` migration: name, slug, city, country, timezone.
     *
     * This is the "no future drift" guard — any new NOT NULL column without
     * a matching ->required() will trip this assertion.
     */
    public function test_all_not_null_db_columns_are_required_in_form_schema(): void
    {
        $required = [];

        foreach ($this->getFormFields() as $name => $field) {
            if ($field->isRequired()) {
                $required[] = $name;
            }
        }

        sort($required);

        $expected = ['city', 'country', 'name', 'slug', 'timezone'];

        $this->assertSame(
            $expected,
            $required,
            'Required form fields must mirror the NOT NULL columns of `locations`.',
        );
    }

    /**
     * Scenario 3 — Country is a Select limited to ISO-3166 alpha-2 codes.
     *
     * Regression net for the country fix: the DB column is `varchar(2)`,
     * so allowing free text caused a 22001 overflow → 500.
     *
     * Given the Admin Location form is built,
     * When the `country` field is inspected,
     * Then it must be a Select component with options whose keys are all
     * exactly 2 characters long.
     */
    public function test_country_field_uses_iso2_codes_only(): void
    {
        $field = $this->getFormField('country');

        $this->assertInstanceOf(
            Select::class,
            $field,
            'The `country` field must be a Select to constrain values to ISO-2 codes.',
        );

        $options = $field->getOptions();
        $this->assertNotEmpty($options, 'Country Select must expose at least one ISO-2 option.');

        foreach (array_keys($options) as $code) {
            $this->assertSame(
                2,
                strlen((string) $code),
                "Country option `{$code}` must be exactly 2 characters (ISO-3166 alpha-2).",
            );
        }
    }

    /**
     * Scenario 4 — Create page renders 200 when the Google Maps key is unset.
     *
     * Regression net for the Map widget fix: prior to the fix, an empty key
     * produced a TypeError from `MapsHelper::mapsKey()` and 500'd the page.
     *
     * Given GOOGLE_MAPS_API_KEY is not configured,
     * When an admin opens the Create Location page,
     * Then the response must be 200.
     */
    public function test_create_page_loads_when_google_maps_key_is_unset(): void
    {
        config(['filament-google-maps.key' => null]);

        $this->actingAs($this->admin)
            ->get('/admin/locations/create')
            ->assertOk();
    }

    /**
     * Scenario 5 — Edit page renders 200 for an existing Location.
     *
     * Belt-and-braces regression check: even with a populated record, the
     * form must load without surfacing the underlying integrations as a 500.
     */
    public function test_edit_page_loads_for_existing_location(): void
    {
        config(['filament-google-maps.key' => null]);

        $location = Location::factory()->create();

        $this->actingAs($this->admin)
            ->get("/admin/locations/{$location->slug}/edit")
            ->assertOk();
    }

    /**
     * Scenario 6 — Happy path: a Location persists when all required
     * non-translatable + translatable fields are supplied.
     *
     * Pure model-level assertion that mirrors what a successful Filament
     * save does: fill non-translatable attributes, set translations, save.
     * Documents the minimal valid payload shape.
     */
    public function test_location_persists_when_all_required_fields_supplied(): void
    {
        $location = Location::create([
            'name' => ['en' => 'Tunis Test', 'fr' => 'Tunis Test'],
            'slug' => 'tunis-test',
            'city' => 'Tunis',
            'country' => 'TN',
            'timezone' => 'Africa/Tunis',
        ]);

        $this->assertDatabaseHas('locations', [
            'slug' => 'tunis-test',
            'city' => 'Tunis',
            'country' => 'TN',
            'timezone' => 'Africa/Tunis',
        ]);
        $this->assertSame('Tunis Test', $location->getTranslation('name', 'en'));
    }

    /**
     * Scenario 7 — Canary: the DB still rejects NULL `city`.
     *
     * If a future migration makes `locations.city` nullable, this test
     * fails loudly so the change is reviewed against the Zod contract
     * (`packages/schemas/src/index.ts` declares `city: z.string()`) and
     * frontend consumers that render `location.city` directly.
     */
    public function test_db_rejects_null_city_at_insert_time(): void
    {
        $this->expectException(QueryException::class);

        Location::create([
            'name' => ['en' => 'No City', 'fr' => 'Sans ville'],
            'slug' => 'no-city',
            'country' => 'TN',
            'timezone' => 'Africa/Tunis',
            // city intentionally omitted
        ]);
    }

    /**
     * Build the LocationResource form schema and return a name → Field map
     * of every leaf Field component.
     *
     * @return array<string, Field>
     */
    private function getFormFields(): array
    {
        $stub = new class extends LivewireComponent implements HasForms
        {
            use InteractsWithForms;

            public function render(): View|string
            {
                return '';
            }
        };

        $form = LocationResource::form(Form::make($stub));

        $fields = [];
        $this->collectFields($form->getComponents(), $fields);

        return $fields;
    }

    private function getFormField(string $name): ?Field
    {
        return $this->getFormFields()[$name] ?? null;
    }

    /**
     * Recursively collect every Field component into $out keyed by name.
     *
     * @param  array<string, Field>  $out
     */
    private function collectFields(array $components, array &$out): void
    {
        foreach ($components as $component) {
            if ($component instanceof Field) {
                $out[$component->getName()] = $component;
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->collectFields($component->getChildComponents(), $out);
            }
        }
    }
}
