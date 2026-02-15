# Troubleshooting

This guide covers common issues and solutions for K-Systems in both development and production environments.

## Quick Diagnostics

Before diving into specific issues, run these checks:

```bash
# Check all containers are running
docker compose ps

# Check container logs for errors
docker compose logs --tail=100

# Check resource usage
docker stats --no-stream

# Check disk space
df -h
```

## Frontend Issues

### Frontend Not Loading (Blank Page)

**Symptoms:** Browser shows a blank white page or "This site can't be reached."

**Solutions:**

1. **Check the frontend container is running:**
   ```bash
   docker compose ps frontend
   docker compose logs frontend --tail=30
   ```

2. **Check Nginx configuration inside the container:**
   ```bash
   docker compose exec frontend nginx -t
   ```

3. **Verify the build output exists:**
   ```bash
   docker compose exec frontend ls -la /usr/share/nginx/html/
   ```

4. **Check Traefik routing (production):**
   ```bash
   # Verify Traefik sees the frontend container
   curl -s http://localhost:8080/api/http/routers | python3 -m json.tool
   ```

5. **Rebuild if necessary:**
   ```bash
   docker compose up -d --build frontend
   ```

### Frontend Shows "API Error" or Cannot Connect to Backend

**Symptoms:** Login fails, pages show loading spinners indefinitely, console shows CORS or network errors.

**Solutions:**

1. **Check the VITE_API_URL build argument matches your domain:**
   ```bash
   # In docker-compose.yml, check the frontend build args:
   # VITE_API_URL should be https://yourdomain.com/api
   ```

2. **Verify the backend is accessible:**
   ```bash
   curl -s https://yourdomain.com/api/
   ```

3. **Check CORS headers:**
   Open browser DevTools (F12) -> Network tab -> Look for blocked requests with CORS errors.

4. **Check the FRONTEND_URL_PROD in backend environment:**
   This must match the actual frontend URL for CORS to work.

### JavaScript Console Errors

**Symptoms:** Features partially work, some buttons do nothing.

**Solutions:**

1. Open browser DevTools (F12) -> Console tab
2. Look for red error messages
3. Common causes:
   - Missing API endpoints (backend version mismatch)
   - WebSocket connection failures
   - Permission denied errors (check user roles)

## Backend Issues

### Backend Returns 500 Internal Server Error

**Symptoms:** API calls return HTTP 500.

**Solutions:**

1. **Check PHP error logs:**
   ```bash
   docker compose logs backend --tail=50
   docker compose exec backend tail -50 /var/log/apache2/error.log
   ```

2. **Verify PHP extensions are loaded:**
   ```bash
   docker compose exec backend php -m | grep -E "mysqli|pdo_mysql|mbstring|json|gd"
   ```

3. **Check the `.env` configuration in the backend container:**
   ```bash
   docker compose exec backend cat /var/www/html/.env
   ```

4. **Verify database connectivity from the backend:**
   ```bash
   docker compose exec backend php -r "
   \$conn = new mysqli('db', 'ksystems', 'ksystems_password', 'ksystems', 3306);
   echo \$conn->connect_error ? 'FAILED: '.\$conn->connect_error : 'OK';
   echo PHP_EOL;
   "
   ```

### Backend Returns 404 for API Routes

**Symptoms:** `/api/some-endpoint` returns 404.

**Solutions:**

1. **Check Apache mod_rewrite is enabled:**
   ```bash
   docker compose exec backend apache2ctl -M | grep rewrite
   ```

2. **Verify .htaccess exists and is read:**
   ```bash
   docker compose exec backend cat /var/www/html/.htaccess
   ```

3. **Check Traefik routing rules:**
   The backend router should match `PathPrefix('/api')`.

### File Upload Fails

**Symptoms:** Uploading files returns an error.

**Solutions:**

1. **Check upload directory permissions:**
   ```bash
   docker compose exec backend ls -la /var/www/html/uploads/
   ```

2. **Verify PHP upload limits:**
   ```bash
   docker compose exec backend php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"
   ```

3. **Check available disk space in the volume:**
   ```bash
   docker system df
   ```

4. **Verify the uploads volume is mounted:**
   ```bash
   docker compose exec backend df -h /var/www/html/uploads/
   ```

## Database Issues

### Database Connection Refused

**Symptoms:** Backend logs show "Connection refused" or "Access denied" for database.

**Solutions:**

1. **Check the database container is running and healthy:**
   ```bash
   docker compose ps db
   docker compose logs db --tail=30
   ```

2. **Verify the health check passes:**
   ```bash
   docker compose exec db mysqladmin ping -h localhost -u root -p"${DB_ROOT_PASSWORD}"
   ```

3. **Check credentials match:**
   Ensure `DB_USERNAME`, `DB_PASSWORD`, and `DB_DATABASE` in `.env` match what was set when the database volume was first created. Changing these values in `.env` does NOT change the already-initialized database.

4. **If credentials are wrong, reset:**
   ```bash
   # Connect as root and reset the user password
   docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" \
       -e "ALTER USER 'ksystems'@'%' IDENTIFIED BY 'new_password'; FLUSH PRIVILEGES;"
   ```

### Database Import Fails

**Symptoms:** `database.sql` import produces errors.

**Solutions:**

1. **Check the database exists:**
   ```bash
   docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" -e "SHOW DATABASES;"
   ```

2. **Create the database if missing:**
   ```bash
   docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" \
       -e "CREATE DATABASE IF NOT EXISTS ksystems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

3. **Import with verbose output to identify the failing statement:**
   ```bash
   docker compose exec -T db mysql -u root -p"${DB_ROOT_PASSWORD}" ksystems -v < database/database.sql 2>&1 | tail -50
   ```

### Slow Database Queries

**Symptoms:** Pages load slowly, API responses take several seconds.

**Solutions:**

1. **Enable and check the slow query log:**
   ```bash
   docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" \
       -e "SET GLOBAL slow_query_log = 'ON'; SET GLOBAL long_query_time = 1;"
   ```

2. **Check table sizes:**
   ```bash
   docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" ksystems \
       -e "SELECT table_name, table_rows, ROUND(data_length/1024/1024, 2) AS size_mb
           FROM information_schema.tables WHERE table_schema = 'ksystems' ORDER BY data_length DESC LIMIT 20;"
   ```

3. **Optimize tables:**
   ```bash
   docker compose exec db mysqlcheck -u root -p"${DB_ROOT_PASSWORD}" --optimize ksystems
   ```

## Socket Server Issues

### Real-Time Features Not Working

**Symptoms:** Chat messages, notifications, whiteboard drawing, and dispatch updates do not appear in real-time. Pages must be refreshed to see changes.

**Solutions:**

1. **Check the socket server is running:**
   ```bash
   docker compose ps socket-server
   docker compose logs socket-server --tail=30
   ```

2. **Verify Socket.io is accessible:**
   ```bash
   curl -s https://yourdomain.com/socket.io/socket.io.js | head -5
   ```

3. **Check the VITE_SOCKET_URL build argument:**
   It should be `wss://yourdomain.com/socket.io` for production.

4. **Check JWT_SECRET matches between backend and socket server:**
   Both services must use the same `JWT_SECRET_KEY` for token validation.

5. **Check the CORS_ORIGIN in socket server environment:**
   Must match `https://yourdomain.com`.

6. **Restart the socket server:**
   ```bash
   docker compose restart socket-server
   ```

### WebSocket Connection Fails in Browser

**Symptoms:** Browser console shows WebSocket connection errors.

**Solutions:**

1. **Check Traefik supports WebSocket upgrades** (it does by default).

2. **If using a custom Nginx proxy, add WebSocket headers:**
   ```nginx
   location /socket.io/ {
       proxy_pass http://socket-server:3001;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "upgrade";
       proxy_set_header Host $host;
   }
   ```

3. **Check firewall is not blocking WebSocket connections.**

## Docker Issues

### Container Keeps Restarting

**Symptoms:** `docker compose ps` shows a container in "Restarting" state.

**Solutions:**

1. **Check the logs for the crash reason:**
   ```bash
   docker compose logs <service-name> --tail=100
   ```

2. **Common causes:**
   - Database not ready yet (backend depends on db health check)
   - Missing environment variables
   - Port conflicts
   - Out of memory

3. **Check memory:**
   ```bash
   docker stats --no-stream
   free -h
   ```

### Port Already in Use

**Symptoms:** `docker compose up` fails with "port is already allocated."

**Solutions:**

1. **Find what is using the port:**
   ```bash
   sudo lsof -i :3307  # Database port
   sudo lsof -i :80    # HTTP
   sudo lsof -i :443   # HTTPS
   ```

2. **Change the port in `.env`:**
   ```bash
   DB_HOST_PORT=3308  # Change from default 3307
   ```

3. **Or stop the conflicting service.**

### Disk Space Full

**Symptoms:** Uploads fail, database errors, containers won't start.

**Solutions:**

1. **Check disk usage:**
   ```bash
   df -h
   docker system df
   ```

2. **Clean up Docker resources:**
   ```bash
   # Remove unused images
   docker image prune -a

   # Remove unused volumes (CAREFUL - only removes unattached volumes)
   docker volume prune

   # Remove build cache
   docker builder prune
   ```

3. **Clean up old backup files:**
   ```bash
   find /backups/k-systems -type f -mtime +30 -delete
   ```

4. **Check log file sizes:**
   ```bash
   docker compose logs --tail=1 2>&1 | wc -c  # Total log size indicator
   ```

## Authentication Issues

### Cannot Log In

**Symptoms:** Login page returns an error or redirects back to login.

**Solutions:**

1. **Verify the backend API is reachable** from the browser (check Network tab in DevTools).

2. **Check JWT_SECRET_KEY is set** in the backend environment and matches the socket server.

3. **Check the database has user data:**
   ```bash
   docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" ksystems \
       -e "SELECT id, username FROM users LIMIT 10;"
   ```

4. **Reset a user password (if you have database access):**
   ```bash
   # Generate a bcrypt hash for a new password
   docker compose exec backend php -r "echo password_hash('newpassword', PASSWORD_BCRYPT) . PHP_EOL;"

   # Update in database
   docker compose exec db mysql -u root -p"${DB_ROOT_PASSWORD}" ksystems \
       -e "UPDATE users SET password='<hash-from-above>' WHERE username='admin';"
   ```

### Session Expires Too Quickly

**Symptoms:** Users are logged out frequently.

**Solutions:**

1. **Check cookie configuration in backend environment:**
   - `COOKIE_DOMAIN` must match your domain
   - `COOKIE_SECURE` should be `true` for HTTPS
   - `COOKIE_SAMESITE` should be `Lax`

2. **Verify the system clock is correct** on the server (JWT tokens use timestamps).

## Performance Issues

### General Slowness

**Solutions:**

1. **Check server resource usage:**
   ```bash
   docker stats --no-stream
   htop
   ```

2. **Check if the database is the bottleneck** (see Slow Database Queries above).

3. **Check network latency:**
   ```bash
   curl -o /dev/null -s -w "Connect: %{time_connect}s\nTTFB: %{time_starttransfer}s\nTotal: %{time_total}s\n" \
       https://yourdomain.com/api/
   ```

4. **Consider scaling up** the server if resources are consistently at capacity.

## Development Environment Issues

### Dev Containers Won't Start

```bash
# Use the dev compose file
docker compose -f docker-compose.dev.yml up --build

# Check for syntax errors in compose file
docker compose -f docker-compose.dev.yml config
```

### Hot Reload Not Working (Frontend Dev)

The development frontend container should mount the source directory. Check that the volume mount in `docker-compose.dev.yml` points to the correct path.

### Cannot Connect to Dev Database

Default development credentials:
- Host: `localhost`
- Port: `3307`
- User: `ksystems`
- Password: `ksystems_password`
- Database: `ksystems`

```bash
mysql -h 127.0.0.1 -P 3307 -u ksystems -pksystems_password ksystems
```

## Getting More Help

If the issue is not covered here:

1. **Check container logs** with `docker compose logs <service> --tail=200`
2. **Check browser DevTools** (F12) -> Console and Network tabs
3. **Check the GitHub repository** for open issues
4. **Review the documentation:**
   - [[Production Deployment]] for setup issues
   - [[Backup Strategy]] for data recovery
   - [[Configuration]] for environment variables
   - [[Architecture Overview]] for understanding the system

---

## Related Pages

- [[Production Deployment]] - Production setup guide
- [[Backup Strategy]] - Backup and recovery procedures
- [[Docker Setup]] - Docker configuration
- [[Configuration]] - Environment variables
- [[Security]] - Security features and best practices
