'use client';

import { useTranslations } from 'next-intl';
import type { ElevationProfile as ElevationProfileType } from '@go-adventure/schemas';
import { TrendingUp, TrendingDown, Mountain } from 'lucide-react';

interface ElevationProfileProps {
  profile: ElevationProfileType;
  className?: string;
}

export default function ElevationProfile({ profile, className = '' }: ElevationProfileProps) {
  const t = useTranslations('itinerary');

  if (!profile.points || profile.points.length === 0) {
    return null;
  }

  const width = 800;
  const height = 250;
  const padding = { top: 20, right: 20, bottom: 40, left: 50 };

  const chartWidth = width - padding.left - padding.right;
  const chartHeight = height - padding.top - padding.bottom;

  // Calculate scales
  const maxDistance = profile.totalDistance;
  const elevationRange = profile.maxElevation - profile.minElevation;
  const elevationPadding = elevationRange * 0.1; // 10% padding

  const xScale = (distance: number) => (distance / maxDistance) * chartWidth;
  const yScale = (elevation: number) =>
    chartHeight -
    ((elevation - profile.minElevation + elevationPadding) /
      (elevationRange + 2 * elevationPadding)) *
      chartHeight;

  // Generate path
  const pathData = profile.points
    .map((point, index) => {
      const x = xScale(point.distance);
      const y = yScale(point.elevation);
      return `${index === 0 ? 'M' : 'L'} ${x} ${y}`;
    })
    .join(' ');

  // Create filled area path
  const areaPath = `
    ${pathData}
    L ${xScale(profile.totalDistance)} ${chartHeight}
    L 0 ${chartHeight}
    Z
  `;

  // Format distance for display
  const formatDistance = (meters: number) => {
    const km = meters / 1000;
    return km < 1 ? `${meters.toFixed(0)}m` : `${km.toFixed(1)}km`;
  };

  return (
    <div className={`space-y-4 ${className}`}>
      <h3 className="text-xl font-semibold text-neutral-900">{t('elevation_profile')}</h3>

      {/* Stats */}
      <div className="grid grid-cols-2 gap-4 rounded-lg border border-neutral-200 bg-neutral-50 p-4 sm:grid-cols-4">
        <div>
          <div className="mb-1 flex items-center gap-1 text-xs text-neutral-600">
            <Mountain className="h-3 w-3" />
            {t('max_elevation')}
          </div>
          <div className="text-lg font-semibold text-neutral-900">
            {profile.maxElevation.toFixed(0)}m
          </div>
        </div>
        <div>
          <div className="mb-1 flex items-center gap-1 text-xs text-neutral-600">
            <Mountain className="h-3 w-3" />
            {t('min_elevation')}
          </div>
          <div className="text-lg font-semibold text-neutral-900">
            {profile.minElevation.toFixed(0)}m
          </div>
        </div>
        <div>
          <div className="mb-1 flex items-center gap-1 text-xs text-neutral-600">
            <TrendingUp className="h-3 w-3" />
            {t('total_ascent')}
          </div>
          <div className="text-lg font-semibold text-green-600">
            {profile.totalAscent.toFixed(0)}m
          </div>
        </div>
        <div>
          <div className="mb-1 flex items-center gap-1 text-xs text-neutral-600">
            <TrendingDown className="h-3 w-3" />
            {t('total_descent')}
          </div>
          <div className="text-lg font-semibold text-orange-600">
            {profile.totalDescent.toFixed(0)}m
          </div>
        </div>
      </div>

      {/* Chart */}
      <div className="overflow-x-auto rounded-lg border border-neutral-200 bg-white p-4">
        <svg
          viewBox={`0 0 ${width} ${height}`}
          className="w-full"
          style={{ maxWidth: '800px', height: 'auto' }}
        >
          {/* Grid lines */}
          <g className="grid-lines">
            {[0, 0.25, 0.5, 0.75, 1].map((percent) => {
              const y = padding.top + chartHeight * percent;
              const elevation =
                profile.maxElevation +
                elevationPadding -
                percent * (elevationRange + 2 * elevationPadding);
              return (
                <g key={percent}>
                  <line
                    x1={padding.left}
                    y1={y}
                    x2={padding.left + chartWidth}
                    y2={y}
                    className="stroke-gray-200"
                    strokeWidth="1"
                  />
                  <text
                    x={padding.left - 10}
                    y={y}
                    textAnchor="end"
                    alignmentBaseline="middle"
                    fontSize="12"
                    className="fill-gray-500"
                  >
                    {elevation.toFixed(0)}m
                  </text>
                </g>
              );
            })}
          </g>

          {/* Elevation area */}
          <g transform={`translate(${padding.left}, ${padding.top})`}>
            <path
              d={areaPath}
              fill="url(#elevationGradient)"
              stroke="none"
              className="elevation-area"
            />
            <path d={pathData} fill="none" className="stroke-primary" strokeWidth="2" />

            {/* Gradient definition */}
            <defs>
              <linearGradient id="elevationGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stopColor="var(--primary)" stopOpacity="0.3" />
                <stop offset="100%" stopColor="var(--primary)" stopOpacity="0.05" />
              </linearGradient>
            </defs>

            {/* Min/Max markers */}
            {profile.points.map((point, index) => {
              if (
                point.elevation === profile.maxElevation ||
                point.elevation === profile.minElevation
              ) {
                const x = xScale(point.distance);
                const y = yScale(point.elevation);
                const isMax = point.elevation === profile.maxElevation;

                return (
                  <g key={`marker-${index}`}>
                    <circle
                      cx={x}
                      cy={y}
                      r="4"
                      className={isMax ? 'fill-green-500' : 'fill-red-500'}
                    />
                    <text
                      x={x}
                      y={y - 10}
                      textAnchor="middle"
                      fontSize="10"
                      className={isMax ? 'fill-green-500' : 'fill-red-500'}
                      fontWeight="600"
                    >
                      {point.elevation.toFixed(0)}m
                    </text>
                  </g>
                );
              }
              return null;
            })}
          </g>

          {/* X-axis */}
          <g transform={`translate(${padding.left}, ${height - padding.bottom})`}>
            <line
              x1="0"
              y1="0"
              x2={chartWidth}
              y2="0"
              className="stroke-gray-300"
              strokeWidth="1"
            />
            {[0, 0.25, 0.5, 0.75, 1].map((percent) => {
              const x = chartWidth * percent;
              const distance = maxDistance * percent;
              return (
                <g key={percent}>
                  <line x1={x} y1="0" x2={x} y2="6" className="stroke-gray-400" strokeWidth="1" />
                  <text x={x} y="20" textAnchor="middle" fontSize="12" className="fill-gray-500">
                    {formatDistance(distance)}
                  </text>
                </g>
              );
            })}
            <text
              x={chartWidth / 2}
              y="38"
              textAnchor="middle"
              fontSize="12"
              className="fill-gray-700"
              fontWeight="500"
            >
              {t('distance')}
            </text>
          </g>
        </svg>
      </div>
    </div>
  );
}
