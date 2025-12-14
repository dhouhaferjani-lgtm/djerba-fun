'use client';

import { useState } from 'react';
import { Input, Button } from '@go-adventure/ui';
import { useTranslations } from 'next-intl';
import { Search } from 'lucide-react';

interface SearchBarProps {
  onSearch?: (query: string) => void;
  placeholder?: string;
  className?: string;
  defaultValue?: string;
}

export function SearchBar({
  onSearch,
  placeholder,
  className = '',
  defaultValue = '',
}: SearchBarProps) {
  const t = useTranslations('common');
  const [query, setQuery] = useState(defaultValue);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSearch?.(query);
  };

  return (
    <form onSubmit={handleSubmit} className={`flex gap-2 ${className}`}>
      <div className="flex-1">
        <Input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={placeholder || t('search')}
          className="w-full"
        />
      </div>
      <Button type="submit" variant="primary">
        <Search className="h-5 w-5 mr-2" />
        {t('search')}
      </Button>
    </form>
  );
}
