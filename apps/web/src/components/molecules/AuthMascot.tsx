'use client';

import { motion, AnimatePresence } from 'framer-motion';
import { useEffect, useState } from 'react';

export type MascotState = 'idle' | 'watching' | 'hiding' | 'success' | 'error';

interface AuthMascotProps {
  state: MascotState;
  watchDirection?: number; // 0-1, how far eyes look left→right
}

export function AuthMascot({ state, watchDirection = 0.5 }: AuthMascotProps) {
  const [isBlinking, setIsBlinking] = useState(false);

  // Blink every 3-5 seconds when idle or watching
  useEffect(() => {
    if (state === 'hiding' || state === 'success') return;

    const blink = () => {
      setIsBlinking(true);
      setTimeout(() => setIsBlinking(false), 150);
    };

    const interval = setInterval(blink, 3000 + Math.random() * 2000);
    return () => clearInterval(interval);
  }, [state]);

  // Eye pupil offset: look straight down when watching, shift right as text grows
  const pupilOffsetX = state === 'watching' ? watchDirection * 6 : 0;
  const pupilOffsetY = state === 'watching' ? 4 : 0;

  // Determine eye shape
  const isHappy = state === 'success';
  const isWorried = state === 'error';
  const eyesClosed = isBlinking;

  return (
    <div className="flex justify-center mb-[-20px] relative z-10">
      <motion.svg
        width="140"
        height="140"
        viewBox="0 0 200 200"
        initial={{ y: -20, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        transition={{ type: 'spring', stiffness: 200, damping: 15 }}
      >
        {/* Body */}
        <motion.g
          animate={
            state === 'idle' ? { y: [0, -2, 0] } : state === 'error' ? { x: [0, -3, 3, -3, 0] } : {}
          }
          transition={
            state === 'idle'
              ? { duration: 3, repeat: Infinity, ease: 'easeInOut' }
              : state === 'error'
                ? { duration: 0.4 }
                : {}
          }
        >
          {/* Body/torso */}
          <ellipse cx="100" cy="170" rx="35" ry="25" fill="#0D642E" />

          {/* Neck */}
          <rect x="90" y="140" width="20" height="20" rx="5" fill="#f5f0d1" />

          {/* Head */}
          <circle cx="100" cy="105" r="45" fill="#f5f0d1" />

          {/* Cheeks (blush) */}
          <circle cx="68" cy="115" r="8" fill="#f5d1d1" opacity="0.5" />
          <circle cx="132" cy="115" r="8" fill="#f5d1d1" opacity="0.5" />

          {/* Eyes */}
          <AnimatePresence mode="wait">
            {state === 'hiding' ? (
              /* Eyes hidden by hands - show closed eyes behind */
              <g key="hidden-eyes">
                <line
                  x1="78"
                  y1="100"
                  x2="90"
                  y2="100"
                  stroke="#1c1917"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                />
                <line
                  x1="110"
                  y1="100"
                  x2="122"
                  y2="100"
                  stroke="#1c1917"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                />
              </g>
            ) : isHappy ? (
              /* Happy crescent eyes */
              <g key="happy-eyes">
                <motion.path
                  d="M78 102 Q84 94 90 102"
                  fill="none"
                  stroke="#1c1917"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                />
                <motion.path
                  d="M110 102 Q116 94 122 102"
                  fill="none"
                  stroke="#1c1917"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                />
              </g>
            ) : eyesClosed ? (
              /* Blinking */
              <g key="blink-eyes">
                <line
                  x1="78"
                  y1="100"
                  x2="90"
                  y2="100"
                  stroke="#1c1917"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                />
                <line
                  x1="110"
                  y1="100"
                  x2="122"
                  y2="100"
                  stroke="#1c1917"
                  strokeWidth="2.5"
                  strokeLinecap="round"
                />
              </g>
            ) : (
              /* Open eyes with moving pupils */
              <g key="open-eyes">
                {/* Left eye white */}
                <ellipse
                  cx="84"
                  cy="100"
                  rx="10"
                  ry={isWorried ? 11 : 10}
                  fill="white"
                  stroke="#1c1917"
                  strokeWidth="1.5"
                />
                {/* Right eye white */}
                <ellipse
                  cx="116"
                  cy="100"
                  rx="10"
                  ry={isWorried ? 11 : 10}
                  fill="white"
                  stroke="#1c1917"
                  strokeWidth="1.5"
                />

                {/* Left pupil */}
                <motion.circle
                  cx={84}
                  cy={100}
                  r="5"
                  fill="#1c1917"
                  animate={{ cx: 84 + pupilOffsetX, cy: 100 + pupilOffsetY }}
                  transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                />
                {/* Left pupil highlight */}
                <motion.circle
                  cx={86}
                  cy={97}
                  r="2"
                  fill="white"
                  animate={{ cx: 86 + pupilOffsetX * 0.5, cy: 97 + pupilOffsetY * 0.5 }}
                  transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                />

                {/* Right pupil */}
                <motion.circle
                  cx={116}
                  cy={100}
                  r="5"
                  fill="#1c1917"
                  animate={{ cx: 116 + pupilOffsetX, cy: 100 + pupilOffsetY }}
                  transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                />
                {/* Right pupil highlight */}
                <motion.circle
                  cx={118}
                  cy={97}
                  r="2"
                  fill="white"
                  animate={{ cx: 118 + pupilOffsetX * 0.5, cy: 97 + pupilOffsetY * 0.5 }}
                  transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                />

                {/* Worried eyebrows */}
                {isWorried && (
                  <>
                    <motion.line
                      x1="76"
                      y1="88"
                      x2="92"
                      y2="84"
                      stroke="#1c1917"
                      strokeWidth="2.5"
                      strokeLinecap="round"
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                    />
                    <motion.line
                      x1="124"
                      y1="88"
                      x2="108"
                      y2="84"
                      stroke="#1c1917"
                      strokeWidth="2.5"
                      strokeLinecap="round"
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                    />
                  </>
                )}
              </g>
            )}
          </AnimatePresence>

          {/* Nose */}
          <ellipse cx="100" cy="112" rx="3" ry="2" fill="#d4b896" />

          {/* Mouth */}
          {isHappy ? (
            <motion.path
              d="M90 120 Q100 132 110 120"
              fill="none"
              stroke="#1c1917"
              strokeWidth="2"
              strokeLinecap="round"
              initial={{ d: 'M92 122 Q100 126 108 122' }}
              animate={{ d: 'M88 120 Q100 134 112 120' }}
              transition={{ type: 'spring', stiffness: 200 }}
            />
          ) : isWorried ? (
            <motion.path
              d="M92 125 Q100 120 108 125"
              fill="none"
              stroke="#1c1917"
              strokeWidth="2"
              strokeLinecap="round"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
            />
          ) : (
            <path
              d="M92 122 Q100 128 108 122"
              fill="none"
              stroke="#1c1917"
              strokeWidth="2"
              strokeLinecap="round"
            />
          )}

          {/* Explorer Hat */}
          <g>
            {/* Hat brim */}
            <ellipse cx="100" cy="68" rx="52" ry="8" fill="#0a5025" />
            {/* Hat dome */}
            <path d="M68 68 Q70 35 100 30 Q130 35 132 68 Z" fill="#0D642E" />
            {/* Hat band */}
            <rect x="68" y="60" width="64" height="8" rx="2" fill="#8BC34A" />
            {/* Hat badge - small compass */}
            <circle cx="100" cy="64" r="4" fill="#f5f0d1" />
            <line x1="100" y1="61" x2="100" y2="67" stroke="#0D642E" strokeWidth="1" />
            <line x1="97" y1="64" x2="103" y2="64" stroke="#0D642E" strokeWidth="1" />
          </g>

          {/* Arms */}
          {/* Left arm */}
          <motion.g
            animate={
              state === 'hiding'
                ? { x: 40, y: -70 }
                : state === 'success'
                  ? { x: -10, y: -80 }
                  : { x: 0, y: 0 }
            }
            transition={{ type: 'spring', stiffness: 200, damping: 15 }}
          >
            {/* Left arm */}
            <path
              d="M70 155 Q50 160 40 170"
              fill="none"
              stroke="#0D642E"
              strokeWidth="10"
              strokeLinecap="round"
            />
            {/* Left hand */}
            <circle cx="40" cy="170" r="8" fill="#f5f0d1" />
          </motion.g>

          {/* Right arm */}
          <motion.g
            animate={
              state === 'hiding'
                ? { x: -40, y: -70 }
                : state === 'success'
                  ? { x: 10, y: -80 }
                  : { x: 0, y: 0 }
            }
            transition={{ type: 'spring', stiffness: 200, damping: 15 }}
          >
            {/* Right arm */}
            <path
              d="M130 155 Q150 160 160 170"
              fill="none"
              stroke="#0D642E"
              strokeWidth="10"
              strokeLinecap="round"
            />
            {/* Right hand */}
            <circle cx="160" cy="170" r="8" fill="#f5f0d1" />
          </motion.g>

          {/* Success confetti particles */}
          {state === 'success' && (
            <>
              {[...Array(6)].map((_, i) => (
                <motion.circle
                  key={i}
                  cx={100 + (i - 3) * 20}
                  cy={50}
                  r="3"
                  fill={['#8BC34A', '#0D642E', '#f5f0d1', '#FF6B6B', '#4ECDC4', '#FFE66D'][i]}
                  initial={{ y: 0, opacity: 1 }}
                  animate={{ y: [-20, -60 - Math.random() * 30], opacity: [1, 0], x: (i - 3) * 10 }}
                  transition={{ duration: 1, delay: i * 0.1, ease: 'easeOut' }}
                />
              ))}
            </>
          )}
        </motion.g>
      </motion.svg>
    </div>
  );
}
