# Configuration

All configuration is done via environment variables. For Docker, these are set in a `.env` file at the project root. For manual setup, edit `backend/.env`.

## Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DOMAIN` | Public domain (production only) | `ksystems.example.com` |
| `DB_DATABASE` | Database name | `ksystems` |
| `DB_USERNAME` | Database user | `ksystems` |
| `DB_PASSWORD` | Database password | (strong password) |
| `DB_ROOT_PASSWORD` | MariaDB root password (Docker only) | (strong password) |
| `JWT_SECRET_KEY` | JWT signing secret (min. 32 chars) | (random string) |
| `SOCKET_API_KEY` | Backend ↔ Socket server API key | (random string) |
| `APP_URL_PROD` | Backend API URL | `https://example.com/api` |
| `FRONTEND_URL_PROD` | Frontend URL | `https://example.com` |
| `CADDY_DOMAIN` | Domain for frontend build | `example.com` |
| `COOKIE_DOMAIN` | Cookie domain | `example.com` |

## Optional Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `production` | Environment (`production`, `development`) |
| `DB_HOST` | `db` | Database hostname |
| `DB_PORT` | `3306` | Database port |
| `JWT_EXPIRATION_TIME` | `3600` | Token lifetime in seconds |
| `JWT_ISSUER` | `K-Systems` | JWT issuer claim |
| `JWT_AUDIENCE` | `K-Systems` | JWT audience claim |
| `COOKIE_SECURE` | `true` | Secure cookies (requires HTTPS) |
| `COOKIE_SAMESITE` | `Lax` | SameSite cookie policy |
| `UPLOAD_BASE_DIR` | `/var/www/html/uploads` | Upload storage path |
| `PUBLIC_UPLOAD_URL` | `/upload` | Public URL for uploads |

## Docker Compose Variables

These are only used in `docker-compose.yml`:

| Variable | Default | Description |
|----------|---------|-------------|
| `STACK_NAME` | (none) | Unique name for Traefik router labels |
| `DB_HOST_PORT` | `3307` | Host port for database access |
| `NODE_ENV` | `production` | Node.js environment |

## Example .env for Production

```env
# Domain
DOMAIN=ksystems.example.com
CADDY_DOMAIN=ksystems.example.com
STACK_NAME=ksystems

# Database
DB_DATABASE=ksystems
DB_USERNAME=ksystems
DB_PASSWORD=super_secure_db_password_here
DB_ROOT_PASSWORD=super_secure_root_password_here

# Authentication
JWT_SECRET_KEY=your_random_32_char_secret_key_here
SOCKET_API_KEY=your_random_socket_api_key_here

# URLs
APP_URL_PROD=https://ksystems.example.com/api
FRONTEND_URL_PROD=https://ksystems.example.com

# Cookies
COOKIE_DOMAIN=ksystems.example.com
COOKIE_SECURE=true
COOKIE_SAMESITE=Lax
```

## Generating Secure Secrets

```bash
# Generate a random JWT secret (64 characters)
openssl rand -base64 48

# Generate a random API key
openssl rand -hex 32
```
