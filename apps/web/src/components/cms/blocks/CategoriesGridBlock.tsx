'use client';

import Image from 'next/image';
import Link from 'next/link';

export interface Category {
  name: string;
  count?: number;
  url?: string;
  image: string;
}

export interface CategoriesGridBlockData {
  categories: Category[];
}

export function CategoriesGridBlock({ categories }: CategoriesGridBlockData) {
  if (!categories || categories.length === 0) return null;

  return (
    <section className="categories-grid-block py-16">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {categories.map((category, index) => (
            <Link
              key={index}
              href={(category.url || '#') as any}
              className="category-card group relative overflow-hidden rounded-lg"
            >
              {/* Category Image */}
              <div className="relative h-48 overflow-hidden">
                <Image
                  src={category.image}
                  alt={category.name}
                  fill
                  className="object-cover transition-transform duration-500 group-hover:scale-110"
                />

                {/* Dark Overlay on Hover */}
                <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
              </div>

              {/* Category Footer */}
              <div className="bg-cream p-4">
                <h3 className="font-display font-semibold text-lg text-primary">{category.name}</h3>

                {category.count !== undefined && category.count > 0 && (
                  <p className="text-sm text-gray-600 mt-1">
                    {category.count} {category.count === 1 ? 'experience' : 'experiences'}
                  </p>
                )}
              </div>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
