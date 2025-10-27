#!/bin/bash

# Log Rotation Script for FadSMS API
# This script manages log files to prevent disk space issues

LOG_DIR="/var/www/api.fadsms.com/storage/logs"
BACKUP_DIR="/var/www/api.fadsms.com/storage/logs/backups"
MAX_LOG_SIZE="100M"
MAX_BACKUP_FILES=30
DATE_SUFFIX=$(date +%Y%m%d_%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Function to rotate a log file
rotate_log() {
    local log_file="$1"
    local log_name=$(basename "$log_file" .log)
    
    if [ -f "$log_file" ]; then
        # Check if log file is larger than MAX_LOG_SIZE
        if [ $(stat -f%z "$log_file" 2>/dev/null || stat -c%s "$log_file" 2>/dev/null) -gt $(numfmt --from=iec "$MAX_LOG_SIZE") ]; then
            echo "Rotating $log_file..."
            
            # Compress and move to backup
            gzip -c "$log_file" > "$BACKUP_DIR/${log_name}_${DATE_SUFFIX}.log.gz"
            
            # Clear the original log file
            > "$log_file"
            
            echo "Rotated $log_file to $BACKUP_DIR/${log_name}_${DATE_SUFFIX}.log.gz"
        else
            echo "$log_file is not large enough to rotate"
        fi
    fi
}

# Function to clean old backup files
clean_old_backups() {
    echo "Cleaning old backup files..."
    
    # Remove backup files older than MAX_BACKUP_FILES
    find "$BACKUP_DIR" -name "*.log.gz" -type f | sort -r | tail -n +$((MAX_BACKUP_FILES + 1)) | xargs -r rm -f
    
    echo "Old backup files cleaned"
}

# Main log files to rotate
LOG_FILES=(
    "$LOG_DIR/laravel.log"
    "$LOG_DIR/sms-api.log"
    "$LOG_DIR/admin-api.log"
    "$LOG_DIR/errors.log"
    "$LOG_DIR/sms-providers.log"
    "$LOG_DIR/frontend-errors.log"
    "$LOG_DIR/performance.log"
)

echo "Starting log rotation at $(date)"

# Rotate each log file
for log_file in "${LOG_FILES[@]}"; do
    rotate_log "$log_file"
done

# Clean old backups
clean_old_backups

# Also clean Laravel's daily log files older than 30 days
find "$LOG_DIR" -name "laravel-*.log" -type f -mtime +30 -delete
find "$LOG_DIR" -name "sms-api-*.log" -type f -mtime +30 -delete
find "$LOG_DIR" -name "admin-api-*.log" -type f -mtime +30 -delete
find "$LOG_DIR" -name "errors-*.log" -type f -mtime +30 -delete
find "$LOG_DIR" -name "sms-providers-*.log" -type f -mtime +30 -delete
find "$LOG_DIR" -name "frontend-errors-*.log" -type f -mtime +30 -delete
find "$LOG_DIR" -name "performance-*.log" -type f -mtime +30 -delete

echo "Log rotation completed at $(date)"

# Show disk usage
echo "Current log directory usage:"
du -sh "$LOG_DIR"
echo "Backup directory usage:"
du -sh "$BACKUP_DIR"
