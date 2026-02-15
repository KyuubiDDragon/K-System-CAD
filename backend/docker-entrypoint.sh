#!/bin/bash
set -e

echo "Starting K-Systems Backend..."

# Function to check if MySQL is ready
wait_for_mysql() {
    echo "Waiting for MySQL to be ready..."
    local max_attempts=30
    local attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if php -r "
            try {
                \$pdo = new PDO('mysql:host=${DB_HOST:-db};port=${DB_PORT:-3306}', '${DB_USERNAME:-root}', '${DB_PASSWORD:-rootpassword}');
                echo 'MySQL is ready';
                exit(0);
            } catch (Exception \$e) {
                exit(1);
            }
        " 2>/dev/null; then
            echo "MySQL connection successful!"
            return 0
        fi
        
        attempt=$((attempt + 1))
        echo "MySQL connection attempt $attempt/$max_attempts failed. Retrying in 2 seconds..."
        sleep 2
    done
    
    echo "Failed to connect to MySQL after $max_attempts attempts"
    return 1
}

# Wait for MySQL to be ready
if ! wait_for_mysql; then
    echo "Exiting due to MySQL connection failure"
    exit 1
fi

# Run database migrations
echo "Running database migrations..."
if php /var/www/html/run_migrations.php; then
    echo "Migrations completed successfully"
else
    echo "Migration failed!"
    exit 1
fi

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground