/**
 * BlogSection Component Tests
 *
 * Purpose: Ensure BlogSection fetches from API instead of using hardcoded data
 * Bug Reference: Issue #2 - Homepage blog section used hardcoded translation keys
 * instead of fetching actual blog posts from the API
 */

import { readFileSync, existsSync } from 'fs';
import { join } from 'path';

describe('BlogSection Component', () => {
  const webAppRoot = join(__dirname, '..');
  const blogSectionPath = join(webAppRoot, 'src', 'components', 'home', 'BlogSection.tsx');
  const blogApiPath = join(webAppRoot, 'src', 'lib', 'api', 'blog.ts');
  const homePagePath = join(webAppRoot, 'src', 'app', '[locale]', 'page.tsx');

  let blogSectionContent: string;

  beforeAll(() => {
    blogSectionContent = readFileSync(blogSectionPath, 'utf-8');
  });

  describe('Server Component Configuration', () => {
    it('should NOT be a client component', () => {
      // CRITICAL: BlogSection should be a Server Component for SEO
      // It should NOT have 'use client' directive
      const useClientPattern = /['"]use client['"]/;
      const hasUseClient = useClientPattern.test(blogSectionContent);

      expect(hasUseClient).toBe(false);
    });

    it('should be an async function (Server Component)', () => {
      // Server Components are async functions
      const asyncFunctionPattern = /export\s+async\s+function\s+BlogSection/;
      const isAsyncFunction = asyncFunctionPattern.test(blogSectionContent);

      expect(isAsyncFunction).toBe(true);
    });
  });

  describe('API Integration', () => {
    it('should import getFeaturedBlogPosts from API', () => {
      // CRITICAL: Must fetch from API, not use hardcoded data
      const importPattern =
        /import\s*{[^}]*getFeaturedBlogPosts[^}]*}\s*from\s*['"]@\/lib\/api\/blog['"]/;
      const hasImport = importPattern.test(blogSectionContent);

      expect(hasImport).toBe(true);
    });

    it('should call getFeaturedBlogPosts function', () => {
      // Verify the function is actually called
      const callPattern = /getFeaturedBlogPosts\s*\(/;
      const hasFunctionCall = callPattern.test(blogSectionContent);

      expect(hasFunctionCall).toBe(true);
    });

    it('should NOT have hardcoded blogPostKeys array', () => {
      // REGRESSION: Old implementation had hardcoded data
      const hardcodedPattern = /const\s+blogPostKeys\s*=/;
      const hasHardcodedData = hardcodedPattern.test(blogSectionContent);

      expect(hasHardcodedData).toBe(false);
    });

    it('should NOT use translation keys for post content', () => {
      // REGRESSION: Old implementation used t(post.titleKey) pattern
      const translationKeyPattern = /t\(post\.(titleKey|excerptKey|categoryKey)\)/;
      const usesTranslationKeys = translationKeyPattern.test(blogSectionContent);

      expect(usesTranslationKeys).toBe(false);
    });
  });

  describe('Props Interface', () => {
    it('should accept locale prop', () => {
      // BlogSection needs locale for i18n API calls
      const localePropsPattern = /interface\s+BlogSectionProps[\s\S]*locale:\s*string/;
      const hasLocaleInInterface = localePropsPattern.test(blogSectionContent);

      // Alternative: check function signature
      const functionSignaturePattern = /function\s+BlogSection\s*\(\s*{\s*locale\s*}/;
      const hasLocaleInSignature = functionSignaturePattern.test(blogSectionContent);

      expect(hasLocaleInInterface || hasLocaleInSignature).toBe(true);
    });
  });

  describe('Error Handling', () => {
    it('should have try-catch for API call', () => {
      // Graceful error handling for API failures
      const tryCatchPattern = /try\s*{[\s\S]*getFeaturedBlogPosts[\s\S]*}\s*catch/;
      const hasTryCatch = tryCatchPattern.test(blogSectionContent);

      expect(hasTryCatch).toBe(true);
    });

    it('should handle empty posts gracefully', () => {
      // Should return null or hide section when no posts
      const emptyCheckPattern = /posts\.length\s*===\s*0|posts\.length\s*<\s*1|!posts\.length/;
      const hasEmptyCheck = emptyCheckPattern.test(blogSectionContent);

      expect(hasEmptyCheck).toBe(true);
    });
  });

  describe('Blog API Client', () => {
    it('should have getFeaturedBlogPosts function exported', () => {
      const blogApiContent = readFileSync(blogApiPath, 'utf-8');

      const exportPattern = /export\s+(async\s+)?function\s+getFeaturedBlogPosts/;
      const hasFunctionExport = exportPattern.test(blogApiContent);

      expect(hasFunctionExport).toBe(true);
    });

    it('should call /blog/posts/featured endpoint', () => {
      const blogApiContent = readFileSync(blogApiPath, 'utf-8');

      const endpointPattern = /\/blog\/posts\/featured/;
      const hasEndpoint = endpointPattern.test(blogApiContent);

      expect(hasEndpoint).toBe(true);
    });
  });

  describe('Homepage Integration', () => {
    it('should pass locale prop to BlogSection in homepage', () => {
      const homePageContent = readFileSync(homePagePath, 'utf-8');

      // Should have <BlogSection locale={locale} /> or similar
      const localePassPattern = /<BlogSection\s+locale={locale}/;
      const passesLocale = localePassPattern.test(homePageContent);

      expect(passesLocale).toBe(true);
    });
  });

  describe('Translations', () => {
    it('should use server-side translations', () => {
      // Server Component should use getTranslations from next-intl/server
      const serverTranslationsPattern =
        /import\s*{[^}]*getTranslations[^}]*}\s*from\s*['"]next-intl\/server['"]/;
      const usesServerTranslations = serverTranslationsPattern.test(blogSectionContent);

      expect(usesServerTranslations).toBe(true);
    });

    it('should NOT use client-side useTranslations', () => {
      // REGRESSION: Client components use useTranslations
      const clientTranslationsPattern =
        /import\s*{[^}]*useTranslations[^}]*}\s*from\s*['"]next-intl['"]/;
      const usesClientTranslations = clientTranslationsPattern.test(blogSectionContent);

      expect(usesClientTranslations).toBe(false);
    });
  });
});

describe('BlogPost Type Definition', () => {
  const blogApiPath = join(__dirname, '..', 'src', 'lib', 'api', 'blog.ts');

  it('should export BlogPost interface', () => {
    const blogApiContent = readFileSync(blogApiPath, 'utf-8');

    const interfacePattern = /export\s+interface\s+BlogPost\s*{/;
    const hasInterface = interfacePattern.test(blogApiContent);

    expect(hasInterface).toBe(true);
  });

  it('should have required fields in BlogPost interface', () => {
    const blogApiContent = readFileSync(blogApiPath, 'utf-8');

    // Check for essential fields
    expect(blogApiContent).toContain('id:');
    expect(blogApiContent).toContain('title:');
    expect(blogApiContent).toContain('slug:');
    expect(blogApiContent).toContain('featuredImage:');
    expect(blogApiContent).toContain('isFeatured:');
  });
});
