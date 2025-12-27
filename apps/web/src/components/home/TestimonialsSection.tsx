'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';

interface Testimonial {
  id: string;
  name: string;
  location: string;
  avatar: string;
  rating: number;
  text: string;
  activity: string;
}

const testimonials: Testimonial[] = [
  {
    id: '1',
    name: 'Sarah Mitchell',
    location: 'London, UK',
    avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150',
    rating: 5,
    text: 'The Sahara desert experience was absolutely magical. Sleeping under the stars in the Berber camp and riding camels at sunset - it was like a dream come true. Our guide was incredibly knowledgeable and made us feel like family.',
    activity: 'Sahara Desert Camel Trek',
  },
  {
    id: '2',
    name: 'Marc Dubois',
    location: 'Paris, France',
    avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
    rating: 5,
    text: "La visite de la médina de Tunis était exceptionnelle. Notre guide nous a fait découvrir des endroits que nous n'aurions jamais trouvés seuls. Une expérience authentique et enrichissante !",
    activity: 'Medina of Tunis Walking Tour',
  },
  {
    id: '3',
    name: 'Emma Thompson',
    location: 'Sydney, Australia',
    avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150',
    rating: 5,
    text: 'The cooking class in Sidi Bou Said was the highlight of our trip! We learned to make couscous from scratch and the views from the terrace were stunning. Highly recommend for food lovers!',
    activity: 'Tunisian Cooking Masterclass',
  },
];

export function TestimonialsSection() {
  const t = useTranslations('home');

  return (
    <section className="py-20 bg-white">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-neutral-900 mb-4">
            {t('testimonials_title')}
          </h2>
          <p className="text-lg text-neutral-600 max-w-2xl mx-auto">{t('testimonials_subtitle')}</p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          {testimonials.map((testimonial) => (
            <div key={testimonial.id} className="bg-neutral-50 rounded-2xl p-8 relative">
              {/* Quote icon */}
              <div className="absolute top-6 right-6 text-[#0D642E]/10">
                <svg className="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                </svg>
              </div>

              <div className="flex items-center gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                  <svg
                    key={i}
                    className={`w-5 h-5 ${i < testimonial.rating ? 'text-secondary' : 'text-neutral-200'}`}
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                  </svg>
                ))}
              </div>

              <p className="text-neutral-600 mb-6 leading-relaxed italic">"{testimonial.text}"</p>

              <div className="flex items-center gap-4">
                <div className="relative w-12 h-12 rounded-full overflow-hidden">
                  <Image
                    src={testimonial.avatar}
                    alt={testimonial.name}
                    fill
                    className="object-cover"
                  />
                </div>
                <div>
                  <p className="font-semibold text-neutral-900">{testimonial.name}</p>
                  <p className="text-sm text-neutral-500">{testimonial.location}</p>
                </div>
              </div>

              <div className="mt-4 pt-4 border-t border-neutral-200">
                <p className="text-sm text-[#0D642E] font-medium">{testimonial.activity}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
