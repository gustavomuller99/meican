FROM node:22-alpine

RUN apk add --no-cache curl

WORKDIR /app

COPY blockchain/package.json blockchain/package-lock.json ./
RUN npm ci

COPY blockchain/ ./

COPY docker_for_build/hardhat/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8545

ENTRYPOINT ["/entrypoint.sh"]
