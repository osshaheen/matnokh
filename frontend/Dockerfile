FROM node:22-alpine AS build
WORKDIR /app
RUN npm i -g npm@11
COPY package.json ./
RUN npm install
COPY . .
RUN npm run build
FROM node:22-alpine AS runtime
WORKDIR /app
ENV NODE_ENV=production HOST=0.0.0.0 PORT=3000
COPY --from=build /app/.output ./.output
EXPOSE 3000
CMD ["node", ".output/server/index.mjs"]
