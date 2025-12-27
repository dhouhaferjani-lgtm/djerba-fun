'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import type { CreateReviewRequest } from '@go-adventure/schemas';

interface ReviewFormProps {
  onSubmit: (data: CreateReviewRequest) => void;
  isSubmitting?: boolean;
}

const StarSelector = ({
  rating,
  onChange,
}: {
  rating: number;
  onChange: (rating: number) => void;
}) => {
  const [hoverRating, setHoverRating] = useState(0);
  const displayRating = hoverRating || rating;

  return (
    <div className="flex gap-1">
      {[1, 2, 3, 4, 5].map((star) => (
        <button
          key={star}
          type="button"
          onClick={() => onChange(star)}
          onMouseEnter={() => setHoverRating(star)}
          onMouseLeave={() => setHoverRating(0)}
          className="transition-transform hover:scale-110"
        >
          <svg
            className={`w-10 h-10 ${star <= displayRating ? 'text-warning fill-warning' : 'text-gray-300'}`}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
          >
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        </button>
      ))}
    </div>
  );
};

export default function ReviewForm({ onSubmit, isSubmitting }: ReviewFormProps) {
  const t = useTranslations('reviews');
  const [rating, setRating] = useState(0);
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [pros, setPros] = useState<string[]>([]);
  const [cons, setCons] = useState<string[]>([]);
  const [currentPro, setCurrentPro] = useState('');
  const [currentCon, setCurrentCon] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {};

    if (rating === 0) {
      newErrors.rating = 'Please select a rating';
    }
    if (title.length < 5 || title.length > 100) {
      newErrors.title = 'Title must be between 5 and 100 characters';
    }
    if (content.length < 20 || content.length > 2000) {
      newErrors.content = 'Review must be between 20 and 2000 characters';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) {
      return;
    }

    onSubmit({
      rating,
      title,
      content,
      pros: pros.length > 0 ? pros : undefined,
      cons: cons.length > 0 ? cons : undefined,
    });
  };

  const addPro = () => {
    if (currentPro.trim()) {
      setPros([...pros, currentPro.trim()]);
      setCurrentPro('');
    }
  };

  const addCon = () => {
    if (currentCon.trim()) {
      setCons([...cons, currentCon.trim()]);
      setCurrentCon('');
    }
  };

  const removePro = (index: number) => {
    setPros(pros.filter((_, i) => i !== index));
  };

  const removeCon = (index: number) => {
    setCons(cons.filter((_, i) => i !== index));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Rating */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          {t('rating')} <span className="text-error">*</span>
        </label>
        <StarSelector rating={rating} onChange={setRating} />
        {errors.rating && <p className="mt-1 text-sm text-error">{errors.rating}</p>}
      </div>

      {/* Title */}
      <div>
        <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
          {t('review_title')} <span className="text-error">*</span>
        </label>
        <input
          type="text"
          id="title"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          maxLength={100}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
          placeholder="Summarize your experience in a few words"
        />
        <div className="flex justify-between mt-1">
          {errors.title ? (
            <p className="text-sm text-error">{errors.title}</p>
          ) : (
            <span className="text-sm text-gray-500">Min. 5 characters</span>
          )}
          <span className="text-sm text-gray-500">{title.length}/100</span>
        </div>
      </div>

      {/* Content */}
      <div>
        <label htmlFor="content" className="block text-sm font-medium text-gray-700 mb-2">
          {t('review_content')} <span className="text-error">*</span>
        </label>
        <textarea
          id="content"
          value={content}
          onChange={(e) => setContent(e.target.value)}
          maxLength={2000}
          rows={6}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent resize-none"
          placeholder="Share your detailed experience..."
        />
        <div className="flex justify-between mt-1">
          {errors.content ? (
            <p className="text-sm text-error">{errors.content}</p>
          ) : (
            <span className="text-sm text-gray-500">Min. 20 characters</span>
          )}
          <span className="text-sm text-gray-500">{content.length}/2000</span>
        </div>
      </div>

      {/* Pros */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          {t('pros')} <span className="text-gray-500">(optional)</span>
        </label>
        <div className="flex gap-2 mb-2">
          <input
            type="text"
            value={currentPro}
            onChange={(e) => setCurrentPro(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addPro())}
            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="What did you like?"
          />
          <button
            type="button"
            onClick={addPro}
            className="px-4 py-2 bg-success text-white rounded-lg hover:bg-success-dark transition-colors"
          >
            {t('add_pro')}
          </button>
        </div>
        {pros.length > 0 && (
          <div className="flex flex-wrap gap-2">
            {pros.map((pro, index) => (
              <div
                key={index}
                className="bg-success-light text-success-dark px-3 py-1 rounded-full text-sm flex items-center gap-2"
              >
                {pro}
                <button
                  type="button"
                  onClick={() => removePro(index)}
                  className="text-success hover:text-success-dark"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fillRule="evenodd"
                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                      clipRule="evenodd"
                    />
                  </svg>
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Cons */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          {t('cons')} <span className="text-gray-500">(optional)</span>
        </label>
        <div className="flex gap-2 mb-2">
          <input
            type="text"
            value={currentCon}
            onChange={(e) => setCurrentCon(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addCon())}
            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
            placeholder="What could be improved?"
          />
          <button
            type="button"
            onClick={addCon}
            className="px-4 py-2 bg-error text-white rounded-lg hover:bg-error-dark transition-colors"
          >
            {t('add_con')}
          </button>
        </div>
        {cons.length > 0 && (
          <div className="flex flex-wrap gap-2">
            {cons.map((con, index) => (
              <div
                key={index}
                className="bg-error-light text-error-dark px-3 py-1 rounded-full text-sm flex items-center gap-2"
              >
                {con}
                <button
                  type="button"
                  onClick={() => removeCon(index)}
                  className="text-error hover:text-error-dark"
                >
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path
                      fillRule="evenodd"
                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                      clipRule="evenodd"
                    />
                  </svg>
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Submit Button */}
      <div className="flex justify-end">
        <button
          type="submit"
          disabled={isSubmitting}
          className="px-8 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isSubmitting ? 'Submitting...' : t('submit')}
        </button>
      </div>
    </form>
  );
}
