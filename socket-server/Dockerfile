FROM node:20-alpine

# Create app directory
WORKDIR /app

# Install curl for healthcheck
RUN apk --no-cache add curl

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy source code
COPY . .

# Create a minimal health endpoint script if it doesn't exist
USER root
RUN echo '// health.js - Minimaler Health-Check Endpunkt\n\
const http = require("http");\n\
const express = require("express");\n\
const app = express();\n\
\n\
// Health endpoint\n\
app.get("/health", (req, res) => {\n\
  res.json({\n\
    status: "ok",\n\
    uptime: process.uptime(),\n\
    timestamp: new Date().toISOString(),\n\
    connections: { main: 0, chat: 0, notification: 0, status: 0 }\n\
  });\n\
});\n\
\n\
// Nur ausführen wenn direkt aufgerufen\n\
if (require.main === module) {\n\
  const port = process.env.PORT || 3001;\n\
  const server = http.createServer(app);\n\
  server.listen(port, () => {\n\
    console.log(`Health server listening on port ${port}`);\n\
  });\n\
} else {\n\
  // Als Modul exportieren\n\
  module.exports = app;\n\
}' > /app/health.js

# Create a starter script that checks if server.js exists and runs appropriate code
RUN echo '#!/bin/sh\n\
if [ -f "/app/server.js" ]; then\n\
  # Run main server if it exists\n\
  node server.js\n\
else\n\
  # Otherwise just run health endpoint\n\
  node health.js\n\
fi' > /app/start.sh && chmod +x /app/start.sh

# Create logs directory and set permissions (als root)
RUN mkdir -p logs && \
    chown -R node:node /app && \
    chmod -R 755 /app

# Now switch to non-root user
USER node

# Expose the port the app runs on
EXPOSE 3001

# Healthcheck - jetzt direkt ohne Skript
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -f http://localhost:3001/health || exit 1

# Command to run the application
CMD ["/app/start.sh"] 