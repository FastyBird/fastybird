FROM node:lts-alpine

# Set working directory
WORKDIR /app

# Install dependencies using Yarn
RUN yarn install

EXPOSE 3000
EXPOSE 6006

CMD ["yarn", "dev"]
