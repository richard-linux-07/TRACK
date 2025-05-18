#!/bin/bash
# Setup monitoring cron job

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Add to crontab (runs every 5 minutes)
(crontab -l 2>/dev/null; echo "*/5 * * * * $SCRIPT_DIR/android_monitor.sh >> $SCRIPT_DIR/../logs/monitor.log 2>&1") | crontab -

echo "Cron job setup complete. Monitoring will run every 5 minutes."