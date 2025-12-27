'use client';

import { ContentBlock } from '@/types/cms';
import { VideoBlock } from './blocks/VideoBlock';
import { ImageBlock } from './blocks/ImageBlock';
import { TextImageBlock } from './blocks/TextImageBlock';
import { CallToActionBlock } from './blocks/CallToActionBlock';
import { QuoteBlock } from './blocks/QuoteBlock';
import { HtmlBlock } from './blocks/HtmlBlock';
import { CardsBlock } from './blocks/CardsBlock';
import { ToursListingBlock } from './blocks/ToursListingBlock';
import { PromoBannerBlock } from './blocks/PromoBannerBlock';
import { CategoriesGridBlock } from './blocks/CategoriesGridBlock';
import { CTAWithBlobsBlock } from './blocks/CTAWithBlobsBlock';

interface BlockRendererProps {
  blocks: ContentBlock[];
}

/**
 * Maps block types to their corresponding components
 */
const BLOCK_COMPONENTS: Record<string, React.ComponentType<any>> = {
  VideoBlock,
  ImageBlock,
  TextImageBlock,
  CallToActionBlock,
  QuoteBlock,
  HtmlBlock,
  CardsBlock,
  ToursListingBlock,
  PromoBannerBlock,
  CategoriesGridBlock,
  CTAWithBlobsBlock,
  TemplateBlock: HtmlBlock, // Template blocks can use HTML renderer
  OverviewBlock: TextImageBlock, // Overview blocks are similar to text/image
  CollapsibleGroupBlock: HtmlBlock, // Render as HTML for now
};

/**
 * Renders a collection of content blocks from CMS
 */
export function BlockRenderer({ blocks }: BlockRendererProps) {
  if (!blocks || blocks.length === 0) {
    return null;
  }

  return (
    <div className="cms-blocks space-y-12">
      {blocks.map((block, index) => {
        const BlockComponent = BLOCK_COMPONENTS[block.type];

        if (!BlockComponent) {
          console.warn(`Unknown block type: ${block.type}`);
          return (
            <div key={index} className="bg-warning-light border border-warning p-4 rounded">
              <p className="text-sm text-warning-dark">
                Unsupported block type: <code>{block.type}</code>
              </p>
            </div>
          );
        }

        return (
          <div key={index} className="cms-block" data-block-type={block.type}>
            <BlockComponent {...block.data} />
          </div>
        );
      })}
    </div>
  );
}
