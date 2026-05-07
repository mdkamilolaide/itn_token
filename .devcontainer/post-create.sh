#!/bin/bash

echo "Setting up development environment..."

# Navigate to lib directory and install composer dependencies
if [ -f "lib/composer.json" ]; then
    echo "Installing Composer dependencies..."
    cd lib
    composer install --prefer-dist --no-progress
    cd ..
fi

# Set correct permissions
echo "Setting file permissions..."
chmod -R 755 /var/www/html

# Wait for database to be ready (longer timeout for large SQL imports)
echo "Waiting for database connection..."
echo "   Note: This may take several minutes if importing a large SQL file..."
max_attempts=120
attempt=0
while ! mysql -h db -u root -e "SELECT 1" > /dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ $attempt -ge $max_attempts ]; then
        echo "   Database not ready after $max_attempts attempts"
        echo "   The SQL import may still be in progress."
        echo "   You can manually check with: mysql -h db -u root -e 'SHOW TABLES' ipolongo_v5"
        echo ""
        echo "   Setup complete (database still initializing)"
        exit 0
    fi
    if [ $((attempt % 10)) -eq 0 ]; then
        echo "   Waiting for database... (attempt $attempt/$max_attempts)"
    fi
    sleep 3
done

echo "  Database connection established!"

# Check if test data was imported
echo "  Checking database..."
table_count=$(mysql -h db -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'ipolongo_v5';" 2>/dev/null || echo "0")
echo "   Found $table_count tables in ipolongo_v5 database"

echo ""
echo "  Development environment setup complete!"
echo ""
echo "  Quick Start:"
echo "   - Web App:      http://localhost"
echo "   - phpMyAdmin:   http://localhost:8080"
echo "   - MySQL:        localhost:3306 (user: root, no password)"
echo ""
echo "  To run tests:"
echo "   cd lib && php vendor/bin/phpunit ../tests/"
echo ""
