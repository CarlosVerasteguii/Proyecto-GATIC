#!/bin/bash
# Performance Measurement Script for GATIC
# Measures: TTFB, Total Time, Response Size, X-Perf-Id

SCENARIO=$1
URL=$2
COOKIE_FILE=$3
N=${4:-10}
OUTPUT_DIR="perf-artifacts/network-events"

mkdir -p "$OUTPUT_DIR"

echo "========================================"
echo "Scenario: $SCENARIO"
echo "URL: $URL"
echo "Iterations: $N"
echo "========================================"

# JSON Output
JSON_FILE="$OUTPUT_DIR/${SCENARIO}.json"
echo "{" > "$JSON_FILE"
echo "  \"scenario\": \"$SCENARIO\"," >> "$JSON_FILE"
echo "  \"url\": \"$URL\"," >> "$JSON_FILE"
echo "  \"timestamp\": \"$(date -Iseconds)\"," >> "$JSON_FILE"
echo "  \"measurements\": [" >> "$JSON_FILE"

# CSV Output for easy analysis
CSV_FILE="$OUTPUT_DIR/${SCENARIO}.csv"
echo "iteration,time_namelookup,time_connect,time_appconnect,time_pretransfer,time_redirect,time_starttransfer,time_total,size_download,http_code,perf_id" > "$CSV_FILE"

for i in $(seq 1 $N); do
    echo "  Iteration $i/$N..."
    
    # Build curl command
    if [ -f "$COOKIE_FILE" ]; then
        CURL_CMD="curl -s -w '\n%{time_namelookup},%{time_connect},%{time_appconnect},%{time_pretransfer},%{time_redirect},%{time_starttransfer},%{time_total},%{size_download},%{http_code}' -H 'Cache-Control: no-cache, no-store, must-revalidate' -H 'Pragma: no-cache' -b $COOKIE_FILE -c $COOKIE_FILE"
    else
        CURL_CMD="curl -s -w '\n%{time_namelookup},%{time_connect},%{time_appconnect},%{time_pretransfer},%{time_redirect},%{time_starttransfer},%{time_total},%{size_download},%{http_code}' -H 'Cache-Control: no-cache, no-store, must-revalidate' -H 'Pragma: no-cache' -c $COOKIE_FILE"
    fi
    
    # Execute curl and capture headers separately for X-Perf-Id
    HEADERS_FILE=$(mktemp)
    BODY_FILE=$(mktemp)
    
    if [ -f "$COOKIE_FILE" ]; then
        curl -s -D "$HEADERS_FILE" -b "$COOKIE_FILE" -c "$COOKIE_FILE" \
          -H "Cache-Control: no-cache, no-store, must-revalidate" \
          -H "Pragma: no-cache" \
          -o "$BODY_FILE" \
          -w "\n%{time_namelookup},%{time_connect},%{time_appconnect},%{time_pretransfer},%{time_redirect},%{time_starttransfer},%{time_total},%{size_download},%{http_code}" \
          "$URL" 2>/dev/null > "${BODY_FILE}.timings"
    else
        curl -s -D "$HEADERS_FILE" -c "$COOKIE_FILE" \
          -H "Cache-Control: no-cache, no-store, must-revalidate" \
          -H "Pragma: no-cache" \
          -o "$BODY_FILE" \
          -w "\n%{time_namelookup},%{time_connect},%{time_appconnect},%{time_pretransfer},%{time_redirect},%{time_starttransfer},%{time_total},%{size_download},%{http_code}" \
          "$URL" 2>/dev/null > "${BODY_FILE}.timings"
    fi
    
    # Extract timings (last line)
    TIMINGS=$(tail -1 "${BODY_FILE}.timings")
    
    # Extract X-Perf-Id from headers
    PERF_ID=$(grep -i "X-Perf-Id" "$HEADERS_FILE" | awk '{print $2}' | tr -d '\r')
    [ -z "$PERF_ID" ] && PERF_ID="null"
    
    # Save to CSV
    echo "$i,$TIMINGS,$PERF_ID" >> "$CSV_FILE"
    
    # Save to JSON
    IFS=',' read -r time_namelookup time_connect time_appconnect time_pretransfer time_redirect time_starttransfer time_total size_download http_code <<< "$TIMINGS"
    
    cat >> "$JSON_FILE" << EOF
    {
      "iteration": $i,
      "timings": {
        "namelookup": $time_namelookup,
        "connect": $time_connect,
        "appconnect": $time_appconnect,
        "pretransfer": $time_pretransfer,
        "redirect": $time_redirect,
        "starttransfer": $time_starttransfer,
        "total": $time_total
      },
      "size_download": $size_download,
      "http_code": $http_code,
      "perf_id": "$PERF_ID",
      "ttfb": $time_starttransfer
    }EOF
    
    if [ $i -lt $N ]; then
        echo "," >> "$JSON_FILE"
    fi
    
    # Cleanup
    rm -f "$HEADERS_FILE" "$BODY_FILE" "${BODY_FILE}.timings"
    
    # Small delay between requests
    sleep 0.3
done

echo "" >> "$JSON_FILE"
echo "  ]" >> "$JSON_FILE"
echo "}" >> "$JSON_FILE"

echo ""
echo "Results saved to:"
echo "  - JSON: $JSON_FILE"
echo "  - CSV: $CSV_FILE"
echo ""
echo "Summary (CSV):"
head -6 "$CSV_FILE"
