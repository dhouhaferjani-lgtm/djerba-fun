# Contact Information Standards

## Centralized Contact Details

All contact information is managed through i18n translation files to ensure consistency across the application.

### Location

**English:** `apps/web/messages/en.json` → `footer` section
**French:** `apps/web/messages/fr.json` → `footer` section

### Current Contact Details

```json
{
  "footer": {
    "phone": "+216 71 123 456",
    "email": "hello@goadventure.tn",
    "address": "15 Avenue Habib Bourguiba, Tunis 1000, Tunisia",
    "hours": "Mon-Fri: 9am-6pm"
  }
}
```

### Usage Guidelines

1. **Always use translation keys** - Never hardcode contact information
2. **Reference footer translations** - Use `t('footer.phone')`, `t('footer.email')`, etc.
3. **Maintain consistency** - Keep the same values across all locales (except translated address format)

### Example Implementation

```tsx
import { useTranslations } from 'next-intl';

function ContactSection() {
  const t = useTranslations('footer');

  return (
    <div>
      <p>Phone: {t('phone')}</p>
      <p>
        Email: <a href={`mailto:${t('email')}`}>{t('email')}</a>
      </p>
      <p>Address: {t('address')}</p>
      <p>Hours: {t('hours')}</p>
    </div>
  );
}
```

### Social Media Links

Social media links are currently placeholder `#` links in the Footer component.

**To update:**
Edit `apps/web/src/components/organisms/Footer.tsx` lines 28, 36, 44:

- Facebook: Line 28 - `href="#"`
- Instagram: Line 36 - `href="#"`
- Twitter/X: Line 44 - `href="#"`

### Updating Contact Information

To update contact details:

1. Edit `apps/web/messages/en.json` → `footer` section
2. Edit `apps/web/messages/fr.json` → `footer` section (use French formatting for address)
3. Ensure both files have the same phone and email values
4. Test in both languages to verify display

### Production Checklist

Before launching:

- [ ] Replace placeholder phone number with real number
- [ ] Replace placeholder email with real support email
- [ ] Update address to actual office/headquarters location
- [ ] Update business hours if different from 9am-6pm
- [ ] Add real social media URLs in Footer component
- [ ] Test mailto: links work correctly
- [ ] Test phone links work on mobile (add tel: protocol if needed)
