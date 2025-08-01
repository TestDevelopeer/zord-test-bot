# PowerShell script for launching Laravel project with Docker

# Set execution policy for current session
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process

Write-Host "Starting Docker containers..." -ForegroundColor Green

# Navigate to docker-compose directory
Set-Location docker_local

# Launch docker-compose
try {
    docker-compose --env-file ../.env up --build -d
    if ($LASTEXITCODE -ne 0) {
        throw "Docker-compose finished with error"
    }
    Write-Host "Docker containers started successfully" -ForegroundColor Green
}
catch {
    Write-Host "Error starting Docker containers: $_" -ForegroundColor Red
    Set-Location ..
    exit 1
}

# Return to project root
Set-Location ..

Write-Host "Waiting for containers to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Get project name from environment variable or use default value
$PROJECT_NAME = $env:PROJECT_NAME
if (-not $PROJECT_NAME) {
    $PROJECT_NAME = "zord-test-bot"
}

$APP_CONTAINER = "${PROJECT_NAME}-app"

Write-Host "Executing Laravel commands in container $APP_CONTAINER..." -ForegroundColor Green

# Execute Laravel commands
try {
    Write-Host "Generating application key..." -ForegroundColor Cyan
    docker exec $APP_CONTAINER php artisan key:generate
    if ($LASTEXITCODE -ne 0) {
        throw "Error generating key"
    }

    Write-Host "Creating and seeding database..." -ForegroundColor Cyan
    docker exec $APP_CONTAINER php artisan migrate:fresh --seed
    if ($LASTEXITCODE -ne 0) {
        throw "Error migrating database"
    }

    Write-Host "Creating Orchid admin..." -ForegroundColor Cyan
    docker exec $APP_CONTAINER php artisan orchid:admin --id=1
    if ($LASTEXITCODE -ne 0) {
        throw "Error creating admin"
    }

    Write-Host "All commands executed successfully!" -ForegroundColor Green
    Write-Host "Application is ready to use" -ForegroundColor Green
}
catch {
    Write-Host "Error executing Laravel commands: $_" -ForegroundColor Red
    Write-Host "Check container logs: docker-compose -f docker_local/docker-compose.yml logs" -ForegroundColor Yellow
    exit 1
}

# Setup Zrok tunneling
Write-Host "Setting up Zrok tunnel..." -ForegroundColor Green

# Check if .env file exists
if (-not (Test-Path ".env")) {
    Write-Host "Creating .env file from .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
}

# Read environment variables from .env
$envContent = Get-Content ".env" | Where-Object { $_ -match "^[^#].*=" }
$envVars = @{}
foreach ($line in $envContent) {
    $parts = $line -split "=", 2
    if ($parts.Length -eq 2) {
        $key = $parts[0].Trim()
        $value = $parts[1].Trim()
        $envVars[$key] = $value
    }
}

# Get Zrok token
$ZROK_TOKEN = $envVars["ZROK_AUTHTOKEN"]
if (-not $ZROK_TOKEN) {
    Write-Host "Warning: ZROK_AUTHTOKEN not found in .env file" -ForegroundColor Yellow
    Write-Host "Please set ZROK_AUTHTOKEN in .env file and run the script again" -ForegroundColor Yellow
} else {
    try {
        # Check if zrok is installed
        $zrokInstalled = Get-Command "zrok" -ErrorAction SilentlyContinue
        if (-not $zrokInstalled) {
            Write-Host "Error: zrok is not installed or not in PATH" -ForegroundColor Red
            Write-Host "Please install zrok and try again" -ForegroundColor Yellow
        } else {
            # Check if zrok is already enabled
            Write-Host "Checking zrok status..." -ForegroundColor Cyan
            $zrokStatusCheck = zrok status 2>$null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "Zrok is already enabled. Disabling first..." -ForegroundColor Yellow
                zrok disable 2>$null
                Start-Sleep -Seconds 2
            }
            
            Write-Host "Enabling zrok with authentication token..." -ForegroundColor Cyan
            
            # Enable zrok with token
            zrok enable $ZROK_TOKEN
            if ($LASTEXITCODE -ne 0) {
                throw "Failed to enable zrok"
            }

            # Get nginx port from docker-compose or use default
            $NGINX_PORT = $envVars["NGINX_PORT"]
            if (-not $NGINX_PORT) {
                $NGINX_PORT = "80"
            }

            Write-Host "Starting zrok public share on localhost:$NGINX_PORT..." -ForegroundColor Cyan
            Write-Host "" -ForegroundColor White
            Write-Host "=== ZROK OUTPUT ===" -ForegroundColor Yellow
            
            # Start zrok share and show output
            zrok share public "localhost:$NGINX_PORT" --headless
        }
    }
    catch {
        Write-Host "Error setting up Zrok tunnel: $_" -ForegroundColor Red
        Write-Host "Application will work without public tunnel" -ForegroundColor Yellow
    }
}

Write-Host "Useful commands:" -ForegroundColor Blue
Write-Host "  - View logs: docker-compose -f docker_local/docker-compose.yml logs -f" -ForegroundColor White
Write-Host "  - Stop containers: docker-compose -f docker_local/docker-compose.yml down" -ForegroundColor White
Write-Host "  - Connect to container: docker exec -it $APP_CONTAINER bash" -ForegroundColor White
Write-Host "  - Check zrok status: zrok status" -ForegroundColor White
Write-Host "  - Stop zrok tunnel: zrok disable" -ForegroundColor White