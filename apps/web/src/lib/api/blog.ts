const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

export interface BlogPost {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  featured_image: string | null;
  tags: string[];
  read_time_minutes: number;
  views_count: number;
  is_featured: boolean;
  status: string;
  published_at: string;
  created_at: string;
  updated_at: string;
  author: {
    id: number;
    name: string;
    avatar_url: string | null;
  };
  category: {
    id: number;
    name: string;
    slug: string;
    color: string;
  } | null;
  seo: {
    title: string;
    description: string;
  };
}

export interface BlogPostsResponse {
  data: BlogPost[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  links?: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

export interface GetBlogPostsParams {
  category?: string;
  tag?: string;
  featured?: boolean;
  search?: string;
  per_page?: number;
  page?: number;
  locale?: string;
}

async function fetchApi<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'Accept-Language': options.headers?.['Accept-Language'] || 'en',
      ...options.headers,
    },
  });

  if (!response.ok) {
    throw new Error(`API Error: ${response.statusText}`);
  }

  return response.json();
}

/**
 * Fetch blog posts with optional filters
 */
export async function getBlogPosts(params: GetBlogPostsParams = {}): Promise<BlogPostsResponse> {
  const queryParams = new URLSearchParams();

  if (params.category) queryParams.append('category', params.category);
  if (params.tag) queryParams.append('tag', params.tag);
  if (params.featured) queryParams.append('featured', '1');
  if (params.search) queryParams.append('search', params.search);
  if (params.per_page) queryParams.append('per_page', params.per_page.toString());
  if (params.page) queryParams.append('page', params.page.toString());

  const query = queryParams.toString();
  const endpoint = query ? `/blog/posts?${query}` : '/blog/posts';

  return fetchApi<BlogPostsResponse>(endpoint, {
    headers: {
      'Accept-Language': params.locale || 'en',
    },
  });
}

/**
 * Fetch a single blog post by slug
 */
export async function getBlogPost(
  slug: string,
  locale: string = 'en'
): Promise<{ data: BlogPost }> {
  return fetchApi<{ data: BlogPost }>(`/blog/posts/${slug}`, {
    headers: {
      'Accept-Language': locale,
    },
  });
}

/**
 * Fetch featured blog posts
 */
export async function getFeaturedBlogPosts(
  limit: number = 3,
  locale: string = 'en'
): Promise<{ data: BlogPost[] }> {
  return fetchApi<{ data: BlogPost[] }>(`/blog/posts/featured?limit=${limit}`, {
    headers: {
      'Accept-Language': locale,
    },
  });
}

/**
 * Fetch related blog posts
 */
export async function getRelatedBlogPosts(
  slug: string,
  locale: string = 'en'
): Promise<{ data: BlogPost[] }> {
  return fetchApi<{ data: BlogPost[] }>(`/blog/posts/${slug}/related`, {
    headers: {
      'Accept-Language': locale,
    },
  });
}
