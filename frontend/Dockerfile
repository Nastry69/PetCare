# ================================================================
# PetCare — Dockerfile Frontend (React 19 + Vite → nginx)
# ================================================================
# Stage 1 : build de production React
# Stage 2 : servir les assets statiques avec nginx
# ================================================================

# ── Stage 1 : Build ─────────────────────────────────────────────
FROM node:22-alpine AS builder

WORKDIR /app

# Copie des manifestes avant le code (optimisation cache Docker)
COPY package.json package-lock.json ./

# npm install (plus robuste que npm ci pour les lockfiles cross-platform)
RUN npm install

# Copie du code source
COPY . .

# Build production (génère /app/dist)
ARG VITE_API_URL=http://localhost:8000/api
ENV VITE_API_URL=${VITE_API_URL}
RUN npm run build

# ── Stage 2 : Serve ─────────────────────────────────────────────
FROM nginx:1.27-alpine

# Config nginx pour la SPA React (fallback index.html)
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Assets compilés depuis le stage 1
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
