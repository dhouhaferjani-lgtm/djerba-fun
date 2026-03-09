import type { ServiceType } from '@djerba-fun/schemas';

/**
 * Service-type color scheme for bold visual identity
 * Each service type has a distinct color family:
 * - Tours: Emerald (green) - forest/adventure feel
 * - Nautical: Navy (blue) - ocean/maritime feel
 * - Accommodation: Orange - warm/welcoming feel
 * - Events: Gold - festive/celebratory feel
 */
export interface ServiceTypeColorScheme {
  /** Light background tint (e.g., emerald-50) */
  bg: string;
  /** Border color (e.g., emerald-200) */
  border: string;
  /** Primary accent for buttons, icons (e.g., emerald-600) */
  accent: string;
  /** Text color on white backgrounds (e.g., emerald-700) */
  text: string;
  /** Hover background (e.g., emerald-100) */
  hoverBg: string;
  /** Fill color for stars/icons (e.g., emerald-500) */
  fill: string;
}

const colorSchemes: Record<ServiceType, ServiceTypeColorScheme> = {
  tour: {
    bg: 'bg-emerald-50',
    border: 'border-emerald-200',
    accent: 'text-emerald-600',
    text: 'text-emerald-700',
    hoverBg: 'hover:bg-emerald-100',
    fill: 'fill-emerald-500',
  },
  nautical: {
    bg: 'bg-navy-50',
    border: 'border-navy-200',
    accent: 'text-navy-600',
    text: 'text-navy-700',
    hoverBg: 'hover:bg-navy-100',
    fill: 'fill-navy-500',
  },
  accommodation: {
    bg: 'bg-orange-50',
    border: 'border-orange-200',
    accent: 'text-orange-600',
    text: 'text-orange-700',
    hoverBg: 'hover:bg-orange-100',
    fill: 'fill-orange-500',
  },
  event: {
    bg: 'bg-gold-50',
    border: 'border-gold-200',
    accent: 'text-gold-600',
    text: 'text-gold-700',
    hoverBg: 'hover:bg-gold-100',
    fill: 'fill-gold-500',
  },
};

/**
 * Get color scheme classes for a given service type
 * Returns full Tailwind classes ready to use with cn()
 */
export function getServiceTypeColors(serviceType: ServiceType): ServiceTypeColorScheme {
  return colorSchemes[serviceType] || colorSchemes.tour;
}

/**
 * Get background color classes for service type
 * Includes accent button styling
 */
export function getServiceTypeButtonClasses(serviceType: ServiceType): string {
  const buttonClasses: Record<ServiceType, string> = {
    tour: 'bg-emerald-600 hover:bg-emerald-700 text-white',
    nautical: 'bg-navy-600 hover:bg-navy-700 text-white',
    accommodation: 'bg-orange-600 hover:bg-orange-700 text-white',
    event: 'bg-gold-600 hover:bg-gold-700 text-white',
  };
  return buttonClasses[serviceType] || buttonClasses.tour;
}

/**
 * Get badge styling for service type
 * Returns classes for the badge container
 */
export function getServiceTypeBadgeClasses(serviceType: ServiceType): string {
  const badgeClasses: Record<ServiceType, string> = {
    tour: 'bg-emerald-100 text-emerald-700',
    nautical: 'bg-navy-100 text-navy-700',
    accommodation: 'bg-orange-100 text-orange-700',
    event: 'bg-gold-100 text-gold-700',
  };
  return badgeClasses[serviceType] || badgeClasses.tour;
}

/**
 * Get the dot/indicator color for badge
 */
export function getServiceTypeDotClasses(serviceType: ServiceType): string {
  const dotClasses: Record<ServiceType, string> = {
    tour: 'bg-emerald-500',
    nautical: 'bg-navy-500',
    accommodation: 'bg-orange-500',
    event: 'bg-gold-500',
  };
  return dotClasses[serviceType] || dotClasses.tour;
}
