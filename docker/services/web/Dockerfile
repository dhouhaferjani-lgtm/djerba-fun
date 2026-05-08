# Djerba Fun - Next.js Frontend Dockerfile (pnpm monorepo)
FROM node:22-alpine AS base

RUN npm install -g pnpm@9

WORKDIR /app

# Dependencies stage — copy workspace skeleton (manifests only) for cacheable installs.
FROM base AS deps

COPY pnpm-workspace.yaml package.json pnpm-lock.yaml ./
COPY packages/schemas/package.json ./packages/schemas/
COPY packages/ui/package.json ./packages/ui/
COPY apps/web/package.json ./apps/web/

RUN pnpm install --frozen-lockfile

# Development stage
FROM base AS development

COPY --from=deps /app /app
COPY packages ./packages
COPY apps/web ./apps/web

WORKDIR /app/apps/web

EXPOSE 3000

ENV HOSTNAME="0.0.0.0"
ENV PORT=3000

CMD ["pnpm", "dev"]

# Builder stage — production build (kept structurally aligned with dev; targets the web filter).
FROM base AS builder

COPY --from=deps /app /app
COPY packages ./packages
COPY apps/web ./apps/web

ENV NEXT_TELEMETRY_DISABLED=1

RUN pnpm --filter @djerba-fun/web build

# Production stage
FROM base AS production

ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

COPY --from=builder /app/apps/web/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/apps/web/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/apps/web/.next/static ./.next/static

USER nextjs

EXPOSE 3000

ENV HOSTNAME="0.0.0.0"
ENV PORT=3000

CMD ["node", "apps/web/server.js"]
