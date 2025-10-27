#!/bin/bash

# SMS Cache Update Cron Job
# Runs every hour to update cached SMS services

LOG_FILE="/var/www/api.fadsms.com/storage/logs/sms_cache.log"
LOCK_FILE="/var/www/api.fadsms.com/storage/sms_cache.lock"

# Function to log with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Check if already running
if [ -f "$LOCK_FILE" ]; then
    log "❌ SMS cache update already running (lock file exists)"
    exit 1
fi

# Create lock file
touch "$LOCK_FILE"

# Start logging
log "🚀 Starting SMS cache update cron job"

# Change to Laravel directory
cd /var/www/api.fadsms.com

# Run the cache update command
log "📡 Executing: php artisan sms:update-cache"
php artisan sms:update-cache >> "$LOG_FILE" 2>&1

# Check exit status
if [ $? -eq 0 ]; then
    log "✅ SMS cache update completed successfully"
else
    log "❌ SMS cache update failed with exit code $?"
fi

# Remove lock file
rm -f "$LOCK_FILE"

log "🏁 SMS cache cron job finished"

