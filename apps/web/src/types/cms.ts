import type {
  Page,
  PageHighlight,
  PageKeyFact,
  PageGalleryItem,
  PagePOI,
  HeroCta,
  ContentBlock as SchemaContentBlock,
} from '@djerba-fun/schemas';

// Re-export types from schemas for convenience
export type { Page, PageHighlight, PageKeyFact, PageGalleryItem, PagePOI, HeroCta };

// Use the schema Page type as the main CMSPage type
export type CMSPage = Page;

// Legacy content block type (for backwards compatibility)
export interface ContentBlock {
  type: string;
  data: Record<string, any>;
}

export interface Menu {
  code: string;
  name: string;
  items: MenuItem[];
}

export interface MenuItem {
  id: number;
  label: string;
  url: string;
  target: string | null;
  order: number;
  parent_id: number | null;
}

// Specific block data types (for legacy flexible content blocks)
export interface VideoBlockData {
  video_url: string;
  title?: string;
  caption?: string;
  background_colour?: string;
}

export interface ImageBlockData {
  image: string;
  title?: string;
  caption?: string;
  copyright?: string;
}

export interface TextImageBlockData {
  content: string;
  image?: string;
  image_position?: 'left' | 'right';
  title?: string;
}

export interface CallToActionBlockData {
  title: string;
  text?: string;
  button_label?: string;
  button_url?: string;
  background_colour?: string;
}

export interface QuoteBlockData {
  quote: string;
  author?: string;
  author_title?: string;
}

export interface HtmlBlockData {
  html: string;
}

export interface CardsBlockData {
  cards: Array<{
    title: string;
    description?: string;
    image?: string;
    link?: string;
  }>;
  columns?: number;
}

export interface ToursListingBlockData {
  listing_type: 'all' | 'tour' | 'nautical' | 'accommodation' | 'event';
  count: number;
  sort_by: 'created_at' | 'title' | 'price' | '-price';
  style: 'grid' | 'carousel' | 'list';
}

export interface PromoBannerBlockData {
  title: string;
  subtitle?: string;
  tag?: string;
  primary_button_label?: string;
  primary_button_url?: string;
  secondary_button_label?: string;
  secondary_button_url?: string;
  background_colour?: 'primary' | 'secondary' | 'accent' | 'dark';
}

export interface CategoriesGridBlockData {
  categories: Array<{
    name: string;
    count?: number;
    url?: string;
    image: string;
  }>;
}

export interface CTAWithBlobsBlockData {
  title: string;
  text?: string;
  button_label: string;
  button_url: string;
  button_variant?: 'primary' | 'secondary' | 'white';
}
