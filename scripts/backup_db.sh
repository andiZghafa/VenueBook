#!/bin/bash

# Configuration
# Change these values according to your production environment if needed
DB_USER="root"
DB_PASS="" # Add password here if your production database has one
DB_NAME="venue_tracker"
BACKUP_DIR="/path/to/backups" # e.g., "/var/backups/venue_tracker"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="$BACKUP_DIR/$DB_NAME-$DATE.sql"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Dump the MySQL database
if [ -z "$DB_PASS" ]; then
    mysqldump -u "$DB_USER" "$DB_NAME" > "$BACKUP_FILE"
else
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"
fi

# Optional: compress the backup to save space
# gzip "$BACKUP_FILE"

# Delete backups older than 7 days (1 week)
find "$BACKUP_DIR" -type f -name "$DB_NAME-*.sql*" -mtime +7 -exec rm {} \;

echo "Backup created successfully: $BACKUP_FILE"
