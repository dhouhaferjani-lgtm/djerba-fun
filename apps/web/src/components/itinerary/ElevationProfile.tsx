'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import type {
  ElevationProfile as ElevationProfileType,
  ItineraryStop,
} from '@go-adventure/schemas';
import { TrendingUp, TrendingDown, Mountain, MapPin, Clock } from 'lucide-react';

interface ElevationProfileProps {
  profile: ElevationProfileType;
  checkpoints?: ItineraryStop[];
  locale?: string;
  className?: string;
}

export default function ElevationProfile({
  profile,
  checkpoints = [],
  locale = 'en',
  className = '',
}: ElevationProfileProps) {
  const t = useTranslations('itinerary');
  const [hoveredPoint, setHoveredPoint] = useState<number | null>(null);

  if (!profile.points || profile.points.length === 0) {
    return null;
  }

  const width = 1000;
  const height = 300;
  const padding = { top: 40, right: 20, bottom: 60, left: 60 };

  const chartWidth = width - padding.left - padding.right;
  const chartHeight = height - padding.top - padding.bottom;

  // Calculate scales
  const maxDistance = profile.totalDistance;
  const elevationRange = profile.maxElevation - profile.minElevation;
  const elevationPadding = elevationRange * 0.15; // 15% padding

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

  // Get checkpoint info
  const getCheckpointInfo = (index: number) => {
    const checkpoint = checkpoints[index];
    if (!checkpoint) return null;

    const title =
      typeof checkpoint.title === 'string'
        ? checkpoint.title
        : checkpoint.title[locale] || checkpoint.title.en;
    const description = checkpoint.description
      ? typeof checkpoint.description === 'string'
        ? checkpoint.description
        : checkpoint.description[locale] || checkpoint.description.en
      : null;

    return { title, description, checkpoint };
  };

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Stats Cards */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div className="bg-white rounded-lg border border-neutral-200 p-4">
          <div className="mb-2 flex items-center gap-2 text-sm text-neutral-600">
            <TrendingUp className="h-4 w-4 text-primary" />
            Total Ascent
          </div>
          <div className="text-2xl font-bold text-heading">{profile.totalAscent.toFixed(0)}m</div>
        </div>
        <div className="bg-white rounded-lg border border-neutral-200 p-4">
          <div className="mb-2 flex items-center gap-2 text-sm text-neutral-600">
            <TrendingDown className="h-4 w-4 text-primary" />
            Total Descent
          </div>
          <div className="text-2xl font-bold text-heading">{profile.totalDescent.toFixed(0)}m</div>
        </div>
        <div className="bg-white rounded-lg border border-neutral-200 p-4">
          <div className="mb-2 flex items-center gap-2 text-sm text-neutral-600">
            <Mountain className="h-4 w-4 text-primary" />
            Max Elevation
          </div>
          <div className="text-2xl font-bold text-heading">{profile.maxElevation.toFixed(0)}m</div>
        </div>
        <div className="bg-white rounded-lg border border-neutral-200 p-4">
          <div className="mb-2 flex items-center gap-2 text-sm text-neutral-600">
            <MapPin className="h-4 w-4 text-primary" />
            Total Distance
          </div>
          <div className="text-2xl font-bold text-heading">
            {formatDistance(profile.totalDistance)}
          </div>
        </div>
      </div>

      {/* Elevation Chart */}
      <div className="relative rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
        <h3 className="text-lg font-semibold text-heading mb-4">Elevation Profile</h3>

        {/* Hover Info Card */}
        {hoveredPoint !== null && checkpoints[hoveredPoint] && (
          <div className="absolute top-4 right-4 z-10 max-w-xs rounded-lg border border-neutral-200 bg-white p-4 shadow-lg">
            <div className="space-y-2">
              <div className="font-semibold text-heading">
                {getCheckpointInfo(hoveredPoint)?.title}
              </div>
              <div className="flex items-center gap-4 text-sm text-neutral-600">
                <div className="flex items-center gap-1">
                  <Mountain className="h-3 w-3" />
                  {profile.points[hoveredPoint]?.elevation.toFixed(0)}m
                </div>
                {checkpoints[hoveredPoint].durationMinutes && (
                  <div className="flex items-center gap-1">
                    <Clock className="h-3 w-3" />
                    {checkpoints[hoveredPoint].durationMinutes}min
                  </div>
                )}
              </div>
              {getCheckpointInfo(hoveredPoint)?.description && (
                <div className="text-sm text-neutral-600 line-clamp-2">
                  {getCheckpointInfo(hoveredPoint)?.description}
                </div>
              )}
            </div>
          </div>
        )}

        <div className="overflow-x-auto">
          <svg
            viewBox={`0 0 ${width} ${height}`}
            className="w-full"
            style={{ maxWidth: '1000px', height: 'auto', minHeight: '300px' }}
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
                      className="stroke-neutral-200"
                      strokeWidth="1"
                      strokeDasharray="4 2"
                    />
                    <text
                      x={padding.left - 10}
                      y={y}
                      textAnchor="end"
                      alignmentBaseline="middle"
                      fontSize="14"
                      className="fill-neutral-600"
                      fontWeight="500"
                    >
                      {elevation.toFixed(0)}m
                    </text>
                  </g>
                );
              })}
            </g>

            {/* Elevation area and line */}
            <g transform={`translate(${padding.left}, ${padding.top})`}>
              {/* Filled area */}
              <path
                d={areaPath}
                fill="url(#elevationGradient)"
                stroke="none"
                className="elevation-area"
              />

              {/* Main line */}
              <path
                d={pathData}
                fill="none"
                className="stroke-primary"
                strokeWidth="3"
                strokeLinecap="round"
                strokeLinejoin="round"
              />

              {/* Gradient definition */}
              <defs>
                <linearGradient id="elevationGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stopColor="#0D642E" stopOpacity="0.25" />
                  <stop offset="100%" stopColor="#0D642E" stopOpacity="0.05" />
                </linearGradient>
              </defs>

              {/* Checkpoint markers */}
              {profile.points.map((point, index) => {
                const x = xScale(point.distance);
                const y = yScale(point.elevation);
                const isHovered = hoveredPoint === index;
                const checkpoint = checkpoints[index];

                // Determine marker type
                const isStart = index === 0;
                const isEnd = index === profile.points.length - 1;
                const isMax = point.elevation === profile.maxElevation;
                const isMin = point.elevation === profile.minElevation;

                let markerColor = '#8BC34A'; // default waypoint color (light green)
                let markerSize = 6;

                if (isStart || isEnd) {
                  markerColor = '#0D642E'; // primary green for start/end
                  markerSize = 8;
                } else if (isMax) {
                  markerColor = '#10b981'; // green for highest
                  markerSize = 7;
                } else if (isMin) {
                  markerColor = '#f59e0b'; // orange for lowest
                  markerSize = 7;
                }

                return (
                  <g
                    key={`checkpoint-${index}`}
                    onMouseEnter={() => setHoveredPoint(index)}
                    onMouseLeave={() => setHoveredPoint(null)}
                    className="cursor-pointer transition-transform hover:scale-110"
                    style={{ transformOrigin: `${x}px ${y}px` }}
                  >
                    {/* Hover area (larger invisible circle for easier hovering) */}
                    <circle
                      cx={x}
                      cy={y}
                      r={isHovered ? 20 : 15}
                      fill="transparent"
                      className="pointer-events-auto"
                    />

                    {/* Marker shadow */}
                    <circle
                      cx={x}
                      cy={y + 1}
                      r={isHovered ? markerSize + 2 : markerSize}
                      fill="black"
                      opacity="0.2"
                    />

                    {/* Marker */}
                    <circle
                      cx={x}
                      cy={y}
                      r={isHovered ? markerSize + 2 : markerSize}
                      fill={markerColor}
                      stroke="white"
                      strokeWidth="2"
                      className="transition-all"
                    />

                    {/* Stop number label for start/end/extremes */}
                    {(isStart || isEnd || isMax || isMin) && (
                      <text
                        x={x}
                        y={y - (isHovered ? markerSize + 14 : markerSize + 10)}
                        textAnchor="middle"
                        fontSize="11"
                        fontWeight="600"
                        className="fill-neutral-700 pointer-events-none"
                      >
                        {isStart ? 'START' : isEnd ? 'END' : `${point.elevation.toFixed(0)}m`}
                      </text>
                    )}
                  </g>
                );
              })}
            </g>

            {/* X-axis */}
            <g transform={`translate(${padding.left}, ${height - padding.bottom})`}>
              <line
                x1="0"
                y1="0"
                x2={chartWidth}
                y2="0"
                className="stroke-neutral-300"
                strokeWidth="2"
              />
              {[0, 0.25, 0.5, 0.75, 1].map((percent) => {
                const x = chartWidth * percent;
                const distance = maxDistance * percent;
                return (
                  <g key={percent}>
                    <line
                      x1={x}
                      y1="0"
                      x2={x}
                      y2="8"
                      className="stroke-neutral-400"
                      strokeWidth="2"
                    />
                    <text
                      x={x}
                      y="24"
                      textAnchor="middle"
                      fontSize="14"
                      className="fill-neutral-600"
                      fontWeight="500"
                    >
                      {formatDistance(distance)}
                    </text>
                  </g>
                );
              })}
              <text
                x={chartWidth / 2}
                y="48"
                textAnchor="middle"
                fontSize="15"
                className="fill-neutral-700"
                fontWeight="600"
              >
                Distance
              </text>
            </g>

            {/* Y-axis label */}
            <text
              x={15}
              y={height / 2}
              textAnchor="middle"
              fontSize="15"
              className="fill-neutral-700"
              fontWeight="600"
              transform={`rotate(-90 15 ${height / 2})`}
            >
              Elevation (m)
            </text>
          </svg>
        </div>

        {/* Legend */}
        <div className="mt-4 flex flex-wrap items-center gap-4 text-sm">
          <div className="flex items-center gap-2">
            <div className="h-3 w-3 rounded-full bg-[#0D642E] border-2 border-white shadow-sm"></div>
            <span className="text-neutral-600">Start/End Points</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="h-3 w-3 rounded-full bg-[#10b981] border-2 border-white shadow-sm"></div>
            <span className="text-neutral-600">Highest Point</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="h-3 w-3 rounded-full bg-[#f59e0b] border-2 border-white shadow-sm"></div>
            <span className="text-neutral-600">Lowest Point</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="h-2.5 w-2.5 rounded-full bg-[#8BC34A] border-2 border-white shadow-sm"></div>
            <span className="text-neutral-600">Waypoints</span>
          </div>
        </div>
      </div>
    </div>
  );
}
