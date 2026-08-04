# =============================================
# Frontend Dockerfile — SvelteKit 5 + Node.js
# =============================================
#
# Penjelasan:
# SvelteKit dengan adapter-node menghasilkan standalone Node.js server.
# Multi-stage build digunakan agar image final hanya berisi file
# yang dibutuhkan untuk runtime (tanpa source code & devDependencies).
#
# PUBLIC_* env vars di SvelteKit di-"bake" saat build time,
# jadi kita perlu passing sebagai build args di docker-compose.

# -----------------------------------------------
# Stage 1: Install dependencies & build
# -----------------------------------------------
FROM node:22-alpine AS build

WORKDIR /app

# Copy package files dulu (Docker layer caching)
# Jika package.json tidak berubah, npm ci akan di-cache
COPY package.json package-lock.json ./

# npm ci = clean install dari lockfile (deterministic & lebih cepat)
RUN npm ci

# Copy seluruh source code
COPY . .

# Build args untuk SvelteKit PUBLIC_* env vars
# Nilai ini di-set di docker-compose.yml
ARG PUBLIC_API_BASE_URL
ARG PUBLIC_STORAGE_BASE_URL

# Set sebagai env var agar Vite bisa membacanya saat build
ENV PUBLIC_API_BASE_URL=$PUBLIC_API_BASE_URL
ENV PUBLIC_STORAGE_BASE_URL=$PUBLIC_STORAGE_BASE_URL

# Build production (menghasilkan folder build/)
RUN npm run build

# Hapus devDependencies, sisakan hanya production deps
RUN npm prune --omit=dev

# -----------------------------------------------
# Stage 2: Production image (minimal)
# -----------------------------------------------
FROM node:22-alpine

LABEL maintainer="kupzed"
LABEL description="Indogreen Project Management — SvelteKit Frontend"

WORKDIR /app

# Copy hanya file yang dibutuhkan dari stage build:
# - build/          = output SvelteKit (server + client assets)
# - package.json    = metadata project
# - node_modules/   = production dependencies saja
COPY --from=build /app/build ./build
COPY --from=build /app/package.json ./
COPY --from=build /app/node_modules ./node_modules

# SvelteKit adapter-node default listen di port 3000
ENV PORT=3000
ENV HOST=0.0.0.0
ENV ORIGIN=http://150.109.11.132

EXPOSE 3000

# Jalankan Node.js server
# build/index.js adalah entry point yang dihasilkan adapter-node
CMD ["node", "build"]
