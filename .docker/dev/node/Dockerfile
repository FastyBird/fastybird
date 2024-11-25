FROM node:lts-alpine

# Set working directory
WORKDIR /app

# Increase Node.js memory limit
ENV NODE_OPTIONS="--max_old_space_size=4096"

# Install dependencies using Yarn
RUN yarn install
RUN yarn global add @rollup/rollup-linux-arm64-musl

EXPOSE 3000
EXPOSE 6006

CMD ["yarn", "dev"]
