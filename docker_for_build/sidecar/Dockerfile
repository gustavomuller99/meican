FROM node:22-alpine

WORKDIR /app

COPY blockchain/sidecar/package.json ./
RUN npm install --omit=dev

COPY blockchain/sidecar/src ./src

COPY docker_for_build/sidecar/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 3000

ENTRYPOINT ["/bin/sh", "/entrypoint.sh"]
