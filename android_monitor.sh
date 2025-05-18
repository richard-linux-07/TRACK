#!/bin/bash
# Parental Control Monitoring Script for Android

# Configuration
DEVICE_TOKEN="YOUR_DEVICE_TOKEN"
SERVER_URL="https://yourdomain.com/api"
INTERVAL=300  # 5 minutes in seconds

while true; do
    # 1. Get current location
    LOCATION_JSON=$(adb shell dumpsys location | grep -A 10 "Last Known Locations")
    LAT=$(echo "$LOCATION_JSON" | grep "fused" | awk '{print $3}' | cut -d',' -f1)
    LON=$(echo "$LOCATION_JSON" | grep "fused" | awk '{print $3}' | cut -d',' -f2)
    
    if [ -n "$LAT" ] && [ -n "$LON" ]; then
        curl -X POST "$SERVER_URL/log_location.php" \
             -H "Content-Type: application/json" \
             -d "{\"token\":\"$DEVICE_TOKEN\",\"lat\":$LAT,\"lon\":$LON}"
    fi

    # 2. Get call logs
    CALLS_JSON=$(adb shell content query --uri content://call_log/calls \
                --projection number,type,duration,date \
                --sort "date DESC" \
                --limit 10 | jq -R -s -c 'split("\n") | map(select(. != ""))')
    
    curl -X POST "$SERVER_URL/log_call.php" \
         -H "Content-Type: application/json" \
         -d "{\"token\":\"$DEVICE_TOKEN\",\"calls\":$CALLS_JSON}"

    # 3. Get recent activity (apps/browser)
    ACTIVITY_JSON=$(adb shell dumpsys activity recents | grep "Recent #" -A 5 | jq -R -s -c 'split("\n") | map(select(. != ""))')
    
    curl -X POST "$SERVER_URL/log_activity.php" \
         -H "Content-Type: application/json" \
         -d "{\"token\":\"$DEVICE_TOKEN\",\"activity\":$ACTIVITY_JSON}"

    sleep $INTERVAL
done