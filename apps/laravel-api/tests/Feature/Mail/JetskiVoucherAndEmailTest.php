<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Mail\BookingConfirmationMail;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\VoucherPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * BDD coverage for the voucher PDF + booking confirmation email rendering
 * on a listing that uses pricing.unit_label (e.g. jetski rental "par jetski").
 *
 * Goal: prove that introducing the unit_label feature does NOT cause:
 *   - PDF generation to error
 *   - The voucher PDF to leak "par personne" / "per person"
 *   - The confirmation email to leak "par personne" / "per person"
 *   - Either artifact to break when the listing has the new field present
 *
 * The voucher template currently shows participant->person_type (the KEY,
 * e.g. "adult") with no per-person suffix; the email shows total_amount.
 * Neither rendering surface has a per-unit suffix today, so this test
 * locks that contract: nothing changes.
 */
final class JetskiVoucherAndEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeJetskiBooking(): Booking
    {
        $vendor = User::factory()->create(['role' => UserRole::VENDOR->value]);
        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Test Nautical Co',
            'company_type' => 'company',
            'tax_id' => 'TN-TEST-1',
            'kyc_status' => \App\Enums\KycStatus::VERIFIED,
        ]);

        $location = Location::factory()->create([
            'slug' => 'djerba-test-' . Str::random(4),
        ]);

        $listing = Listing::factory()->create([
            'vendor_id' => $vendor->id,
            'service_type' => ServiceType::NAUTICAL,
            'status' => ListingStatus::PUBLISHED,
            'location_id' => $location->id,
            'title' => ['fr' => 'Jetski 30 min', 'en' => 'Jetski 30 min'],
            'pricing' => [
                'pricing_model' => 'per_person',
                'unit_label' => ['fr' => 'par jetski', 'en' => 'per jetski'],
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['fr' => 'Adulte', 'en' => 'Adult'],
                        'tnd_price' => 105,
                        'eur_price' => 35,
                    ],
                ],
            ],
        ]);

        $slot = AvailabilitySlot::factory()->create([
            'listing_id' => $listing->id,
            'capacity' => 4,
            'remaining_capacity' => 4,
        ]);

        $traveler = User::factory()->create(['role' => UserRole::TRAVELER->value]);

        $booking = Booking::factory()->create([
            'listing_id' => $listing->id,
            'availability_slot_id' => $slot->id,
            'user_id' => $traveler->id,
            'status' => BookingStatus::CONFIRMED,
            'currency' => 'EUR',
            'total_amount' => 35.00,
            'quantity' => 1,
            'locale' => 'fr',
        ]);

        BookingParticipant::create([
            'booking_id' => $booking->id,
            'voucher_code' => 'TEST-VCH-' . Str::random(6),
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'person_type' => 'adult',
            'checked_in' => false,
        ]);

        return $booking->fresh(['listing', 'availabilitySlot', 'participants']);
    }

    /**
     * GIVEN: a confirmed jetski booking on a listing with pricing.unit_label set
     * WHEN:  the single-voucher PDF is generated
     * THEN:  generation succeeds (returns binary PDF), and the underlying
     *        Blade-rendered HTML never leaks "par personne" or "per person".
     *        The vendor's unit_label DOES appear in the listing data
     *        accessible to the template (passed via $listing).
     */
    public function test_voucher_pdf_renders_for_jetski_listing_without_per_person_leak(): void
    {
        $booking = $this->makeJetskiBooking();
        $participant = $booking->participants->first();

        $service = new VoucherPdfService;
        $pdfBinary = $service->generateSingleVoucher($participant);

        // Sanity: it's a real PDF, not an error string.
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
        $this->assertGreaterThan(1000, strlen($pdfBinary));

        // The Blade template renders participant->person_type and listing
        // metadata. We render the view directly to scan its HTML output for
        // any "par personne" leak (PDFs are hard to text-grep but the upstream
        // HTML is the source of truth).
        $listing = $booking->listing;
        $slot = $booking->availabilitySlot;
        $html = view('pdf.voucher', [
            'participant' => $participant,
            'booking' => $booking,
            'listing' => $listing,
            'slot' => $slot,
            'qrCode' => 'data:image/png;base64,fake',
            'platformName' => 'Test',
            'logoUrl' => null,
            'colors' => [
                'primary' => '#000',
                'accent' => '#000',
                'cream' => '#fff',
            ],
        ])->render();

        // Lock the current behavior: the voucher does NOT render a
        // per-person/per-unit price suffix. The unit_label feature only
        // affects the public-site headline, not server-rendered artifacts.
        $this->assertStringNotContainsString('par personne', $html);
        $this->assertStringNotContainsString('per person', $html);
        $this->assertStringNotContainsString('per_person', $html);
    }

    /**
     * GIVEN: a confirmed jetski booking
     * WHEN:  the BookingConfirmationMail is rendered
     * THEN:  the rendered body never leaks "par personne" / "per person",
     *        and rendering does not throw.
     */
    public function test_booking_confirmation_email_renders_for_jetski_without_per_person_leak(): void
    {
        $booking = $this->makeJetskiBooking();

        $mail = new BookingConfirmationMail($booking);
        // Build the message via Laravel's preview path (renders the Blade
        // without dispatching). The Mailable contract returns content
        // through the `render()` helper available on Mailable objects.
        $rendered = $mail->render();

        $this->assertNotEmpty($rendered);
        $this->assertStringNotContainsString('par personne', $rendered);
        $this->assertStringNotContainsString('per person', $rendered);
        $this->assertStringNotContainsString('per_person', $rendered);

        // Sanity: the rendered email mentions the booking number.
        $this->assertStringContainsString((string) $booking->booking_number, $rendered);
    }
}
