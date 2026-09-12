FROM node:22-alpine

RUN apk add --no-cache curl

WORKDIR /app

COPY blockchain/ ./
RUN npm ci

COPY docker_for_build/hardhat/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8545

ENTRYPOINT ["/entrypoint.sh"]
