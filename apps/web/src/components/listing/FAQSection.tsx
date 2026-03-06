'use client';

import { useTranslations } from 'next-intl';
import { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from '@djerba-fun/ui';
import type { ListingFaq } from '@djerba-fun/schemas';

interface FAQSectionProps {
  faqs: ListingFaq[];
}

export function FAQSection({ faqs }: FAQSectionProps) {
  const t = useTranslations('listing.faqs');

  if (!faqs || faqs.length === 0) {
    return null;
  }

  // Filter only active FAQs and sort by order
  const activeFaqs = faqs.filter((faq) => faq.isActive).sort((a, b) => a.order - b.order);

  if (activeFaqs.length === 0) {
    return null;
  }

  return (
    <section className="space-y-4">
      <h2 className="text-2xl font-bold">{t('title')}</h2>

      <Accordion type="single" collapsible className="w-full">
        {activeFaqs.map((faq) => (
          <AccordionItem key={faq.id} value={faq.id}>
            <AccordionTrigger>
              {typeof faq.question === 'string'
                ? faq.question
                : faq.question.en || faq.question.fr || ''}
            </AccordionTrigger>
            <AccordionContent>
              <div className="text-neutral-700">
                {typeof faq.answer === 'string' ? faq.answer : faq.answer.en || faq.answer.fr || ''}
              </div>
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>
    </section>
  );
}
