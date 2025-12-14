'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- typed routes for dynamic hrefs */
import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { Button } from '@go-adventure/ui';
import { useRouter } from 'next/navigation';
import { Award, Leaf, Globe } from 'lucide-react';

export function FeaturedPackagesSection() {
  const t = useTranslations('home');
  const router = useRouter();

  const featuredPackages = [
    {
      icon: <Award className="w-12 h-12" />,
      title: t('featured_package_1_title'),
      description: t('featured_package_1_description'),
      image: '/images/package-1.jpg', // Placeholder image
      link: '/en/listings?type=tour&destination=djerba',
    },
    {
      icon: <Leaf className="w-12 h-12" />,
      title: t('featured_package_2_title'),
      description: t('featured_package_2_description'),
      image: '/images/package-2.jpg', // Placeholder image
      link: '/en/listings?type=event&category=eco-tourism',
    },
    {
      icon: <Globe className="w-12 h-12" />,
      title: t('featured_package_3_title'),
      description: t('featured_package_3_description'),
      image: '/images/package-3.jpg', // Placeholder image
      link: '/en/listings?type=tour&category=adventure',
    },
  ];

  return (
    <section className="py-16 bg-accent-light">
      <div className="container mx-auto px-4 text-center">
        <h2 className="text-4xl font-bold text-primary mb-12">{t('featured_packages_title')}</h2>
        <div className="grid md:grid-cols-3 gap-8">
          {featuredPackages.map((pkg, index) => (
            <div
              key={index}
              className="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden flex flex-col"
            >
              <div className="relative h-48 w-full">
                <Image src={pkg.image} alt={pkg.title} fill className="object-cover" />
              </div>
              <div className="p-8 flex flex-col flex-grow">
                <div className="text-primary mb-4 flex justify-center">{pkg.icon}</div>
                <h3 className="text-xl font-bold text-neutral-darker mb-4">{pkg.title}</h3>
                <p className="text-neutral-dark leading-relaxed mb-6 flex-grow">
                  {pkg.description}
                </p>
                <Button
                  variant="primary"
                  className="mt-auto w-full"
                  onClick={() => router.push(pkg.link as any)}
                >
                  {t('learn_more')}
                </Button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
