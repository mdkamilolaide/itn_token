# Development Container Setup

This directory contains the configuration for a VS Code Dev Container that provides a complete PHP 8.4 development environment with MySQL for testing.

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop)
- [VS Code](https://code.visualstudio.com/)
- [Dev Containers extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

## Quick Start

1. **Open in VS Code**: Open this project folder in VS Code
2. **Reopen in Container**: Press `F1` → "Dev Containers: Reopen in Container"
3. **Wait for build**: The container will build and start automatically
4. **Access the app**: 
   - Web App: http://localhost
   - phpMyAdmin: http://localhost:8080

## Services

| Service | URL | Description |
|---------|-----|-------------|
| PHP App | http://localhost | Main application |
| phpMyAdmin | http://localhost:8080 | Database management |
| MySQL | localhost:3306 | Database server |

## Database

- **Host**: `db` (inside container) or `localhost` (from host)
- **Port**: `3306`
- **Database**: `ipolongo_v5`
- **Username**: `root`
- **Password**: (empty)

The test data from `db/ipolongo_v5 20250615_160043.sql` is automatically loaded when the container starts.

## Changing the Database Seed File

The MySQL container imports a single SQL file on first startup. To change it:

1. Replace or add your SQL file under [db](db).
2. Update the volume mapping in [.devcontainer/docker-compose.yml](.devcontainer/docker-compose.yml) so the file path matches your new SQL file.
3. Recreate the database volume so MySQL re-imports the seed file:
   - Stop containers and remove volumes.
   - Start the containers again.

Note: MySQL only runs the init SQL file when the data directory is empty. If you keep the volume, the new file will not be imported.

## Running Tests

```bash
# Run all tests
cd lib && php vendor/bin/phpunit ../tests/

# Run specific test suite
cd lib && php vendor/bin/phpunit ../tests/Unit/

# Run with coverage report
cd lib && php vendor/bin/phpunit ../tests/ --coverage-html ../tests/coverage/html
```

## Testing PHP Version Upgrades

To test with a different PHP version:

1. Edit `.devcontainer/Dockerfile`
2. Change `FROM php:8.0-apache` to desired version (e.g., `php:8.2-apache`, `php:8.3-apache`)
3. Rebuild the container: `F1` → "Dev Containers: Rebuild Container"
4. Run tests to check compatibility

### Recommended Testing Process

1. **Baseline**: Run tests with current PHP 8.0 and ensure all pass
2. **Upgrade**: Change to PHP 8.1, rebuild, run tests
3. **Iterate**: Continue upgrading (8.2, 8.3) and running tests
4. **Fix issues**: Address any deprecation warnings or failures

## Files

| File | Purpose |
|------|---------|
| `devcontainer.json` | VS Code Dev Container configuration |
| `docker-compose.yml` | Docker services (PHP, MySQL, phpMyAdmin) |
| `Dockerfile` | Custom PHP image with extensions |
| `post-create.sh` | Setup script run after container creation |
| `config.dev.php` | Development configuration reference |
| `.env.example` | Environment variables template |

## Installed PHP Extensions

- pdo, pdo_mysql, mysqli
- mbstring, xml, soap
- gd (with freetype and jpeg)
- bcmath, zip, pcntl
- xdebug (for debugging and coverage)

## Xdebug Configuration

Xdebug is pre-configured for:
- Development mode
- Step debugging
- Code coverage

To debug in VS Code, add a launch configuration for PHP debugging.

## Troubleshooting

### Container won't start
```bash
docker-compose -f .devcontainer/docker-compose.yml down -v
docker-compose -f .devcontainer/docker-compose.yml up --build
```

### Database connection fails
- Ensure the `db` service is healthy
- Check that `DB_HOST=db` is set in environment
- Verify the SQL file was imported correctly

### Permission issues
```bash
sudo chown -R www-data:www-data /var/www/html
```
