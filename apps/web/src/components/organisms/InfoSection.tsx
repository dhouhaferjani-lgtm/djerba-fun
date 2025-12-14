'use client';

import { useTranslations } from 'next-intl';
import { Lightbulb, Rocket, Heart } from 'lucide-react';

export function InfoSection() {
  const t = useTranslations('home');

  const infoItems = [
    {
      icon: <Lightbulb className="w-10 h-10 text-primary" />,
      title: t('info_innovation_title'),
      description: t('info_innovation_description'),
    },
    {
      icon: <Rocket className="w-10 h-10 text-primary" />,
      title: t('info_experience_title'),
      description: t('info_experience_description'),
    },
    {
      icon: <Heart className="w-10 h-10 text-primary" />,
      title: t('info_community_title'),
      description: t('info_community_description'),
    },
  ];

  return (
    <section className="py-20 bg-white">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-neutral-darker mb-4">
            {t('info_section_title')}
          </h2>
          <p className="text-lg text-neutral-dark max-w-2xl mx-auto">
            {t('info_section_subtitle')}
          </p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          {infoItems.map((item, index) => (
            <div key={index} className="flex flex-col items-center text-center p-6 rounded-lg">
              <div className="mb-6">{item.icon}</div>
              <h3 className="text-xl font-bold text-neutral-darker mb-3">{item.title}</h3>
              <p className="text-neutral-dark leading-relaxed">{item.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
