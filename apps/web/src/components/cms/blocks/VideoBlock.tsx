'use client';

import { VideoBlockData } from '@/types/cms';

export function VideoBlock({ video_url, title, caption }: VideoBlockData) {
  // Extract video ID from YouTube or Vimeo URL
  const getVideoEmbedUrl = (url: string): string => {
    // YouTube
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
      const videoId = url.includes('youtu.be')
        ? url.split('/').pop()
        : new URL(url).searchParams.get('v');
      return `https://www.youtube.com/embed/${videoId}`;
    }

    // Vimeo
    if (url.includes('vimeo.com')) {
      const videoId = url.split('/').pop();
      return `https://player.vimeo.com/video/${videoId}`;
    }

    return url;
  };

  const embedUrl = getVideoEmbedUrl(video_url);

  return (
    <div className="video-block">
      {title && <h3 className="text-2xl font-bold mb-4">{title}</h3>}

      <div className="aspect-video">
        <iframe
          src={embedUrl}
          title={title || 'Video'}
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
          className="w-full h-full rounded-lg"
        />
      </div>

      {caption && <p className="text-sm text-gray-600 mt-2 text-center">{caption}</p>}
    </div>
  );
}
