'use client';

import { cn } from '@/lib/utils/cn';
import type { TagGroup, Tag } from '@go-adventure/schemas';

interface TagFilterGroupProps {
  tagGroups: TagGroup[];
  selectedTags: string[];
  onTagToggle: (tagSlug: string) => void;
  className?: string;
  locale?: string;
}

/**
 * Renders tag filter groups for listing filtering.
 * Each group (tour_type, boat_type, etc.) shows as a section with toggleable tag chips.
 */
export function TagFilterGroup({
  tagGroups,
  selectedTags,
  onTagToggle,
  className,
  locale = 'en',
}: TagFilterGroupProps) {
  if (!tagGroups || tagGroups.length === 0) {
    return null;
  }

  return (
    <div className={cn('space-y-4', className)}>
      {tagGroups.map((group) => (
        <div key={group.type}>
          <h4 className="text-sm font-medium text-neutral-700 mb-2">{group.label}</h4>
          <div className="flex flex-wrap gap-2">
            {group.tags.map((tag: Tag) => {
              const isSelected = selectedTags.includes(tag.slug);
              const tagName: string =
                typeof tag.name === 'string'
                  ? tag.name
                  : (tag.name as Record<string, string>)[locale] ||
                    (tag.name as Record<string, string>).en ||
                    '';

              return (
                <button
                  key={tag.id}
                  onClick={() => onTagToggle(tag.slug)}
                  className={cn(
                    'px-3 py-1.5 rounded-full text-sm font-medium transition-all',
                    'border border-neutral-200 hover:border-primary/50',
                    isSelected
                      ? 'bg-primary text-white border-primary'
                      : 'bg-white text-neutral-700 hover:bg-primary/5'
                  )}
                  style={
                    isSelected && tag.color
                      ? { backgroundColor: tag.color, borderColor: tag.color }
                      : undefined
                  }
                >
                  {tagName}
                  {tag.listingsCount > 0 && (
                    <span
                      className={cn(
                        'ml-1.5 text-xs',
                        isSelected ? 'text-white/80' : 'text-neutral-400'
                      )}
                    >
                      ({tag.listingsCount})
                    </span>
                  )}
                </button>
              );
            })}
          </div>
        </div>
      ))}
    </div>
  );
}

interface TagChipProps {
  tag: Tag;
  isSelected: boolean;
  onClick: () => void;
  locale?: string;
}

/**
 * Individual tag chip component for standalone use.
 */
export function TagChip({ tag, isSelected, onClick, locale = 'en' }: TagChipProps) {
  const tagName: string =
    typeof tag.name === 'string'
      ? tag.name
      : (tag.name as Record<string, string>)[locale] ||
        (tag.name as Record<string, string>).en ||
        '';

  return (
    <button
      onClick={onClick}
      className={cn(
        'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium transition-all',
        'border border-neutral-200 hover:border-primary/50',
        isSelected
          ? 'bg-primary text-white border-primary'
          : 'bg-neutral-50 text-neutral-600 hover:bg-primary/5'
      )}
      style={
        isSelected && tag.color ? { backgroundColor: tag.color, borderColor: tag.color } : undefined
      }
    >
      {tagName}
    </button>
  );
}
