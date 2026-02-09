'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { useState, useCallback } from 'react';
import { ChevronLeft, ChevronRight, Star } from 'lucide-react';

interface Testimonial {
  id: string;
  name: string;
  avatar: string;
  text: string;
}

export interface CmsTestimonial {
  name: string;
  photo: string;
  text_en: string;
  text_fr: string;
}

const defaultTestimonials: Testimonial[] = [
  {
    id: '1',
    name: 'Nathalie',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&q=80',
    text: "Merci à Seif de m'avoir accompagner à découvrir Djerba hors des sentiers battus en mobilité douce sans impact pour l'environnement ! Le ravitaillement traditionnel avec la Zomita accompagnée de fruits frais a été l'occasion de me rapprocher du mode de vie des Djerbiens !",
  },
  {
    id: '2',
    name: 'Pierre',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&q=80',
    text: "Une expérience incroyable dans le désert tunisien. L'organisation était parfaite et notre guide connaissait tous les secrets de la région. Je recommande vivement Go Adventure pour découvrir la Tunisie autrement !",
  },
  {
    id: '3',
    name: 'Marie',
    avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&q=80',
    text: "Le vélo dans les montagnes de Tunisie était magnifique. Paysages à couper le souffle et accueil chaleureux des locaux. Une aventure que je ne suis pas prête d'oublier !",
  },
];

interface TestimonialsSectionProps {
  testimonials?: CmsTestimonial[];
  locale?: string;
}

export function TestimonialsSection({
  testimonials: cmsTestimonials,
  locale,
}: TestimonialsSectionProps) {
  const t = useTranslations('home');

  // Map CMS data to internal format, or fall back to hardcoded defaults
  const testimonials: Testimonial[] =
    cmsTestimonials && cmsTestimonials.length > 0
      ? cmsTestimonials.map((item, index) => ({
          id: String(index + 1),
          name: item.name,
          avatar:
            item.photo || 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&q=80',
          text: locale === 'en' ? item.text_en : item.text_fr,
        }))
      : defaultTestimonials;

  const [currentIndex, setCurrentIndex] = useState(0);

  const goToPrevious = useCallback(() => {
    setCurrentIndex((prev) => (prev === 0 ? testimonials.length - 1 : prev - 1));
  }, [testimonials.length]);

  const goToNext = useCallback(() => {
    setCurrentIndex((prev) => (prev === testimonials.length - 1 ? 0 : prev + 1));
  }, [testimonials.length]);

  const currentTestimonial = testimonials[currentIndex];

  return (
    <section className="py-16 md:py-20 bg-cream">
      <div className="container mx-auto px-4">
        <div className="max-w-6xl mx-auto">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            {/* Left Side - Stats */}
            <div>
              <h2 className="text-2xl md:text-3xl font-display font-bold text-neutral-900 mb-3">
                {t('testimonials_title')}
              </h2>
              <p className="text-neutral-600 mb-8">{t('testimonials_subtitle')}</p>

              <div className="flex gap-12">
                {/* Feedback Count */}
                <div>
                  <p className="text-4xl md:text-5xl font-display font-bold text-neutral-900">
                    {t('testimonials_feedback_count')}
                  </p>
                  <p className="text-sm text-neutral-600 mt-1">
                    {t('testimonials_feedback_label')}
                  </p>
                </div>

                {/* Rating */}
                <div>
                  <p className="text-4xl md:text-5xl font-display font-bold text-neutral-900">
                    {t('testimonials_rating')}
                  </p>
                  <div className="flex items-center gap-1 mt-1">
                    <p className="text-sm text-neutral-600 mr-2">
                      {t('testimonials_rating_label')}
                    </p>
                    <div className="flex">
                      {[...Array(5)].map((_, i) => (
                        <Star key={i} className="w-4 h-4 fill-yellow-400 text-yellow-400" />
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Right Side - Testimonial Carousel */}
            <div className="bg-white rounded-2xl p-6 md:p-8 shadow-sm">
              {/* Avatar and Name */}
              <div className="flex items-center gap-4 mb-6">
                <div className="relative w-14 h-14 rounded-full overflow-hidden ring-2 ring-primary/20">
                  <Image
                    src={currentTestimonial.avatar}
                    alt={currentTestimonial.name}
                    fill
                    className="object-cover"
                  />
                </div>
                <p className="font-semibold text-lg text-neutral-900">{currentTestimonial.name}</p>
              </div>

              {/* Testimonial Text */}
              <blockquote className="text-neutral-700 leading-relaxed mb-8 min-h-[120px]">
                {currentTestimonial.text}
              </blockquote>

              {/* Navigation */}
              <div className="flex items-center justify-between">
                {/* Page Indicator */}
                <div className="flex items-center gap-3">
                  <span className="text-sm font-medium text-neutral-900">
                    {String(currentIndex + 1).padStart(2, '0')}
                  </span>
                  <div className="w-24 h-0.5 bg-neutral-200 rounded-full overflow-hidden">
                    <div
                      className="h-full bg-neutral-900 transition-all duration-300"
                      style={{ width: `${((currentIndex + 1) / testimonials.length) * 100}%` }}
                    />
                  </div>
                  <span className="text-sm text-neutral-400">
                    {String(testimonials.length).padStart(2, '0')}
                  </span>
                </div>

                {/* Arrow Buttons */}
                <div className="flex gap-2">
                  <button
                    onClick={goToPrevious}
                    className="p-2 rounded-full border border-neutral-300 hover:border-neutral-400 hover:bg-neutral-50 transition-colors"
                    aria-label="Previous testimonial"
                  >
                    <ChevronLeft className="w-5 h-5 text-neutral-600" />
                  </button>
                  <button
                    onClick={goToNext}
                    className="p-2 rounded-full border border-neutral-300 hover:border-neutral-400 hover:bg-neutral-50 transition-colors"
                    aria-label="Next testimonial"
                  >
                    <ChevronRight className="w-5 h-5 text-neutral-600" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
