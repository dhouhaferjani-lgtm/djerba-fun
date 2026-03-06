'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { useState, useCallback, useEffect, useRef } from 'react';
import { ChevronLeft, ChevronRight, Star } from 'lucide-react';
import { motion, AnimatePresence, type Variants } from 'framer-motion';

// --- Inline hooks ---

function useCountUp(end: number, duration: number, decimals: number, trigger: boolean) {
  const [value, setValue] = useState(0);
  const [done, setDone] = useState(false);

  useEffect(() => {
    if (!trigger) {
      // Reset when trigger goes false (section leaves viewport)
      setValue(0);
      setDone(false);
      return;
    }
    const start = performance.now();
    const step = (now: number) => {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      // easeOutExpo
      const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
      setValue(eased * end);
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        setDone(true);
      }
    };
    requestAnimationFrame(step);
  }, [trigger, end, duration]);

  return { value: decimals > 0 ? value.toFixed(decimals) : Math.floor(value).toString(), done };
}

// --- Framer-motion variants ---

const containerVariants: Variants = {
  enter: (direction: number) => ({
    x: direction > 0 ? 80 : -80,
    opacity: 0,
  }),
  center: {
    x: 0,
    opacity: 1,
    transition: {
      x: { type: 'spring', stiffness: 300, damping: 30 },
      opacity: { duration: 0.2 },
      staggerChildren: 0.1,
    },
  },
  exit: (direction: number) => ({
    x: direction > 0 ? -80 : 80,
    opacity: 0,
    transition: {
      x: { type: 'spring', stiffness: 300, damping: 30 },
      opacity: { duration: 0.15 },
    },
  }),
};

const childVariants: Variants = {
  enter: (direction: number) => ({
    x: direction > 0 ? 30 : -30,
    opacity: 0,
  }),
  center: {
    x: 0,
    opacity: 1,
    transition: { type: 'spring', stiffness: 300, damping: 30 },
  },
  exit: (direction: number) => ({
    x: direction > 0 ? -30 : 30,
    opacity: 0,
    transition: { duration: 0.15 },
  }),
};

// --- Interfaces ---

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
    text: 'Une découverte incroyable de Djerba hors des sentiers battus ! Le tour en calèche à travers les villages traditionnels et le ravitaillement avec la Zomita ont été des moments inoubliables. Je recommande vivement !',
  },
  {
    id: '2',
    name: 'Pierre',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&q=80',
    text: "Le jet ski et le parachute ascensionnel étaient fantastiques ! L'équipe d'Evasion Djerba est très professionnelle et l'organisation parfaite. Une journée mémorable en famille !",
  },
  {
    id: '3',
    name: 'Marie',
    avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&q=80',
    text: "La visite de la synagogue de la Ghriba et le tour culturel de Houmt Souk étaient passionnants. Notre guide connaissait tous les secrets de l'île. Une expérience authentique !",
  },
];

const AUTOPLAY_INTERVAL = 5000;

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

  // --- IntersectionObserver — replays on every scroll in/out ---
  const statsRef = useRef<HTMLDivElement>(null);
  const [isInView, setIsInView] = useState(false);

  useEffect(() => {
    const el = statsRef.current;
    if (!el) return;
    const observer = new IntersectionObserver(
      ([entry]) => {
        setIsInView(entry.isIntersecting);
      },
      { threshold: 0.3 }
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  // --- Count-up animations ---
  const feedbackCountStr = t('testimonials_feedback_count'); // e.g. "500+"
  const feedbackNum = parseFloat(feedbackCountStr.replace(/[^0-9.]/g, '')) || 500;
  const feedbackSuffix = feedbackCountStr.replace(/[0-9.]/g, ''); // e.g. "+"

  const ratingStr = t('testimonials_rating'); // e.g. "4.88"
  const ratingNum = parseFloat(ratingStr) || 4.88;
  const ratingDecimals = (ratingStr.split('.')[1] || '').length || 2;

  const feedbackCounter = useCountUp(feedbackNum, 2000, 0, isInView);
  const ratingCounter = useCountUp(ratingNum, 2000, ratingDecimals, isInView);

  // --- Carousel state ---
  const [currentIndex, setCurrentIndex] = useState(0);
  const [direction, setDirection] = useState(1);
  const isPausedRef = useRef(false);
  const intervalRef = useRef<NodeJS.Timeout | null>(null);
  const [progressKey, setProgressKey] = useState(0);

  const resetAutoplay = useCallback(() => {
    if (intervalRef.current) clearInterval(intervalRef.current);
    setProgressKey((k) => k + 1);

    const advance = () => {
      if (isPausedRef.current) return;
      setDirection(1);
      setCurrentIndex((prev) => (prev + 1) % testimonials.length);
    };

    intervalRef.current = setInterval(advance, AUTOPLAY_INTERVAL);
  }, [testimonials.length]);

  useEffect(() => {
    resetAutoplay();
    return () => {
      if (intervalRef.current) clearInterval(intervalRef.current);
    };
  }, [resetAutoplay]);

  const goToPrevious = useCallback(() => {
    setDirection(-1);
    setCurrentIndex((prev) => (prev === 0 ? testimonials.length - 1 : prev - 1));
    resetAutoplay();
  }, [testimonials.length, resetAutoplay]);

  const goToNext = useCallback(() => {
    setDirection(1);
    setCurrentIndex((prev) => (prev === testimonials.length - 1 ? 0 : prev + 1));
    resetAutoplay();
  }, [testimonials.length, resetAutoplay]);

  const handleMouseEnter = () => {
    isPausedRef.current = true;
  };

  const handleMouseLeave = () => {
    isPausedRef.current = false;
  };

  const currentTestimonial = testimonials[currentIndex];

  return (
    <section className="py-16 md:py-20 bg-cream">
      {/* Pop animation keyframes */}
      <style
        dangerouslySetInnerHTML={{
          __html: `
            @keyframes pop {
              0% { transform: scale(1); }
              50% { transform: scale(1.15); }
              100% { transform: scale(1); }
            }
            .animate-pop { animation: pop 0.3s ease-out; }
          `,
        }}
      />

      <div className="container mx-auto px-4">
        <div className="max-w-6xl mx-auto">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            {/* Left Side - Stats */}
            <div ref={statsRef}>
              <h2 className="text-2xl md:text-3xl font-display font-bold text-neutral-900 mb-3">
                {t('testimonials_title')}
              </h2>
              <p className="text-neutral-600 mb-8">{t('testimonials_subtitle')}</p>

              <div className="flex gap-12">
                {/* Feedback Count */}
                <div>
                  <p
                    className={`text-4xl md:text-5xl font-display font-bold text-neutral-900 ${feedbackCounter.done ? 'animate-pop' : ''}`}
                  >
                    {isInView
                      ? feedbackCounter.done
                        ? feedbackCountStr
                        : `${feedbackCounter.value}${feedbackSuffix}`
                      : `0${feedbackSuffix}`}
                  </p>
                  <p className="text-sm text-neutral-600 mt-1">
                    {t('testimonials_feedback_label')}
                  </p>
                </div>

                {/* Rating */}
                <div>
                  <p
                    className={`text-4xl md:text-5xl font-display font-bold text-neutral-900 ${ratingCounter.done ? 'animate-pop' : ''}`}
                  >
                    {isInView ? (ratingCounter.done ? ratingStr : ratingCounter.value) : '0.00'}
                  </p>
                  <div className="flex items-center gap-0.5 mt-2">
                    {[...Array(5)].map((_, i) => (
                      <Star
                        key={i}
                        className={`w-5 h-5 transition-all duration-300 ${
                          ratingCounter.done
                            ? 'fill-yellow-400 text-yellow-400 scale-100 opacity-100'
                            : 'fill-transparent text-neutral-300 scale-75 opacity-40'
                        }`}
                        style={{
                          transitionDelay: ratingCounter.done ? `${i * 150}ms` : '0ms',
                        }}
                      />
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Right Side - Testimonial Carousel */}
            <div
              className="bg-white rounded-2xl p-6 md:p-8 shadow-sm overflow-hidden"
              onMouseEnter={handleMouseEnter}
              onMouseLeave={handleMouseLeave}
            >
              {/* Animated testimonial content with framer-motion */}
              <AnimatePresence mode="wait" initial={false} custom={direction}>
                <motion.div
                  key={currentIndex}
                  custom={direction}
                  variants={containerVariants}
                  initial="enter"
                  animate="center"
                  exit="exit"
                >
                  {/* Child 1: Avatar + Name */}
                  <motion.div
                    className="flex items-center gap-4 mb-6"
                    variants={childVariants}
                    custom={direction}
                  >
                    <div className="relative w-14 h-14 rounded-full overflow-hidden ring-2 ring-primary/20 flex-shrink-0">
                      <Image
                        src={currentTestimonial.avatar}
                        alt={currentTestimonial.name}
                        fill
                        className="object-cover"
                      />
                    </div>
                    <p className="font-semibold text-lg text-neutral-900">
                      {currentTestimonial.name}
                    </p>
                  </motion.div>

                  {/* Child 2: Quote text */}
                  <motion.blockquote
                    className="text-neutral-700 leading-relaxed mb-8 min-h-[120px]"
                    variants={childVariants}
                    custom={direction}
                  >
                    {currentTestimonial.text}
                  </motion.blockquote>
                </motion.div>
              </AnimatePresence>

              {/* Navigation */}
              <div className="flex items-center justify-between">
                {/* Page Indicator with timer progress */}
                <div className="flex items-center gap-3">
                  <span className="text-sm font-medium text-neutral-900">
                    {String(currentIndex + 1).padStart(2, '0')}
                  </span>
                  <div className="w-24 h-0.5 bg-neutral-200 rounded-full overflow-hidden">
                    <div
                      key={progressKey}
                      className="h-full bg-neutral-900 rounded-full"
                      style={{
                        width: `${((currentIndex + 1) / testimonials.length) * 100}%`,
                        transition: `width ${AUTOPLAY_INTERVAL}ms linear`,
                      }}
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
