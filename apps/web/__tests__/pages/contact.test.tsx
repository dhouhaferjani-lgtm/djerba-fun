/**
 * Contact Page Tests
 *
 * Purpose: Ensure Contact page exists with functional form
 *
 * BDD Scenarios:
 * - Contact page file exists at correct location
 * - Contact page has form with name, email, message fields
 * - Contact page handles form submission
 * - Translations exist for contact page content
 */

import { existsSync, readFileSync } from 'fs';
import { join } from 'path';

describe('Contact Page', () => {
  const webAppRoot = join(__dirname, '..', '..');
  const contactPagePath = join(webAppRoot, 'src', 'app', '[locale]', 'contact', 'page.tsx');
  const contactApiPath = join(webAppRoot, 'src', 'lib', 'api', 'contact.ts');
  const enMessagesPath = join(webAppRoot, 'messages', 'en.json');
  const frMessagesPath = join(webAppRoot, 'messages', 'fr.json');

  describe('Page File Structure', () => {
    it('should exist at correct location', () => {
      // Contact page must exist at apps/web/src/app/[locale]/contact/page.tsx
      const exists = existsSync(contactPagePath);
      expect(exists).toBe(true);
    });

    it('should be a valid TypeScript/React file', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should have default export
      expect(content).toMatch(/export\s+default/);
    });

    it('should handle locale parameter', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should receive params with locale
      expect(content).toMatch(/params.*locale|locale.*params/);
    });
  });

  describe('Contact Form Elements', () => {
    it('should have form element', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      expect(content).toMatch(/<form|Form/);
    });

    it('should have name input field', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should have input for name
      const hasNameField =
        content.includes('name="name"') ||
        content.includes("name='name'") ||
        content.includes('id="name"') ||
        content.includes("id='name'") ||
        content.includes('...register("name")') ||
        content.includes("...register('name')");
      expect(hasNameField).toBe(true);
    });

    it('should have email input field', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should have input for email
      const hasEmailField =
        content.includes('name="email"') ||
        content.includes("name='email'") ||
        content.includes('id="email"') ||
        content.includes("id='email'") ||
        content.includes('type="email"') ||
        content.includes("type='email'") ||
        content.includes('...register("email")') ||
        content.includes("...register('email')");
      expect(hasEmailField).toBe(true);
    });

    it('should have message textarea field', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should have textarea for message
      const hasMessageField =
        content.includes('name="message"') ||
        content.includes("name='message'") ||
        content.includes('<textarea') ||
        content.includes('Textarea') ||
        content.includes('...register("message")') ||
        content.includes("...register('message')");
      expect(hasMessageField).toBe(true);
    });

    it('should have submit button', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should have submit button
      const hasSubmitButton =
        content.includes('type="submit"') ||
        content.includes("type='submit'") ||
        content.includes('Submit') ||
        content.includes('submit');
      expect(hasSubmitButton).toBe(true);
    });
  });

  describe('Form Validation', () => {
    it('should use form validation (React Hook Form or similar)', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should use form validation library
      const hasValidation =
        content.includes('useForm') ||
        content.includes('react-hook-form') ||
        content.includes('formState') ||
        content.includes('required') ||
        content.includes('zod') ||
        content.includes('yup');
      expect(hasValidation).toBe(true);
    });
  });

  describe('API Integration', () => {
    it('should have contact API client file', () => {
      const exists = existsSync(contactApiPath);
      expect(exists).toBe(true);
    });

    it('should export submitContactForm function', () => {
      const content = readFileSync(contactApiPath, 'utf-8');
      expect(content).toMatch(/export.*submitContactForm|submitContactForm.*export/);
    });

    it('should call POST /api/v1/contact endpoint', () => {
      const content = readFileSync(contactApiPath, 'utf-8');
      expect(content).toContain('/contact');
    });
  });

  describe('Contact Information Display', () => {
    it('should display phone number', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should display contact phone
      const hasPhone =
        content.includes('phone') || content.includes('Phone') || content.includes('+216');
      expect(hasPhone).toBe(true);
    });

    it('should display email address', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should display contact email
      const hasEmail =
        content.includes('email') ||
        content.includes('Email') ||
        content.includes('contact@go-adventure');
      expect(hasEmail).toBe(true);
    });

    it('should display physical address', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should display address
      const hasAddress =
        content.includes('address') || content.includes('Address') || content.includes('Djerba');
      expect(hasAddress).toBe(true);
    });
  });

  describe('Translations', () => {
    it('should have contact section in English translations', () => {
      const enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
      expect(enMessages.contact_page || enMessages.contact).toBeDefined();
    });

    it('should have contact section in French translations', () => {
      const frMessages = JSON.parse(readFileSync(frMessagesPath, 'utf-8'));
      expect(frMessages.contact_page || frMessages.contact).toBeDefined();
    });

    it('should have form field labels in English', () => {
      const enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
      const contactSection = enMessages.contact_page || enMessages.contact;
      // Should have labels for form fields
      const hasLabels =
        contactSection?.name_label ||
        contactSection?.email_label ||
        contactSection?.message_label ||
        contactSection?.form?.name;
      expect(hasLabels).toBeDefined();
    });

    it('should have form field labels in French', () => {
      const frMessages = JSON.parse(readFileSync(frMessagesPath, 'utf-8'));
      const contactSection = frMessages.contact_page || frMessages.contact;
      // Should have labels for form fields
      const hasLabels =
        contactSection?.name_label ||
        contactSection?.email_label ||
        contactSection?.message_label ||
        contactSection?.form?.name;
      expect(hasLabels).toBeDefined();
    });

    it('should have success message in translations', () => {
      const enMessages = JSON.parse(readFileSync(enMessagesPath, 'utf-8'));
      const contactSection = enMessages.contact_page || enMessages.contact;
      // Should have success message for form submission
      const hasSuccessMessage =
        contactSection?.success || contactSection?.success_message || contactSection?.thank_you;
      expect(hasSuccessMessage).toBeDefined();
    });
  });

  describe('Error Handling', () => {
    it('should have error state handling', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should handle errors
      const hasErrorHandling =
        content.includes('error') ||
        content.includes('Error') ||
        content.includes('catch') ||
        content.includes('isError');
      expect(hasErrorHandling).toBe(true);
    });

    it('should have loading state', () => {
      const content = readFileSync(contactPagePath, 'utf-8');
      // Should have loading indicator
      const hasLoadingState =
        content.includes('loading') ||
        content.includes('Loading') ||
        content.includes('isLoading') ||
        content.includes('isSubmitting') ||
        content.includes('pending');
      expect(hasLoadingState).toBe(true);
    });
  });
});

describe('Contact Page SEO', () => {
  const webAppRoot = join(__dirname, '..', '..');
  const contactPagePath = join(webAppRoot, 'src', 'app', '[locale]', 'contact', 'page.tsx');

  it('should export metadata for SEO', () => {
    const content = readFileSync(contactPagePath, 'utf-8');
    const hasMetadata =
      content.includes('generateMetadata') || content.includes('export const metadata');
    expect(hasMetadata).toBe(true);
  });
});
