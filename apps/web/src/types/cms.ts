export interface CMSPage {
  id: number;
  code: string | null;
  slug: string;
  title: string;
  intro: string | null;
  hero_image: string | null;
  hero_image_copyright: string | null;
  hero_image_title: string | null;
  hero_call_to_actions: Array<{
    label: string;
    url: string;
    style?: string;
  }> | null;
  seo_title: string | null;
  seo_description: string | null;
  seo_keywords: string[] | null;
  seo_image: string | null;
  overview_title: string | null;
  overview_description: string | null;
  overview_image: string | null;
  content_blocks: ContentBlock[];
  publishing_begins_at: string | null;
  publishing_ends_at: string | null;
  author?: {
    id: number;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

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

// Specific block data types
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
