import { CMSPage, Menu } from '@/types/cms';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

async function fetchApi<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers: {
      ...headers,
      ...options.headers,
    },
  });

  if (!response.ok) {
    throw new Error(`API Error: ${response.statusText}`);
  }

  return response.json();
}

export interface GetPagesParams {
  locale?: string;
}

export interface GetPageParams {
  slug: string;
  locale?: string;
}

export interface GetPageByCodeParams {
  code: string;
  locale?: string;
}

export interface GetMenuParams {
  menuCode: string;
  locale?: string;
}

/**
 * Fetch all published pages
 */
export async function getPages(params: GetPagesParams = {}): Promise<CMSPage[]> {
  const { locale = 'en' } = params;
  const query = new URLSearchParams({ locale });

  const response = await fetchApi<{ data: CMSPage[] }>(`/pages?${query}`);
  return response.data;
}

/**
 * Fetch a single page by slug
 */
export async function getPage(params: GetPageParams): Promise<CMSPage> {
  const { slug, locale = 'en' } = params;
  const query = new URLSearchParams({ locale });

  const response = await fetchApi<{ data: CMSPage }>(`/pages/${slug}?${query}`);
  return response.data;
}

/**
 * Fetch a page by code (for special pages like HOME)
 */
export async function getPageByCode(params: GetPageByCodeParams): Promise<CMSPage | null> {
  const { code, locale = 'en' } = params;
  const query = new URLSearchParams({ locale });

  try {
    const response = await fetchApi<{ data: CMSPage }>(`/pages/code/${code}?${query}`);
    return response.data;
  } catch (error) {
    // Page not found - return null to trigger fallback
    console.warn(`Page with code ${code} not found`);
    return null;
  }
}

/**
 * Fetch a menu by code
 */
export async function getMenu(params: GetMenuParams): Promise<Menu> {
  const { menuCode, locale = 'en' } = params;
  const query = new URLSearchParams({ locale });

  return fetchApi<Menu>(`/menus/${menuCode}?${query}`);
}
