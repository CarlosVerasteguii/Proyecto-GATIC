#!/bin/bash
# Script de medición de performance para GATIC
# Uso: ./measure.sh <scenario_name> <url> <cookie_file> <n_iterations>

SCENARIO=$1
URL=$2
COOKIE_FILE=$3
N=${4:-10}
OUTPUT_DIR="perf-artifacts/network-events"

mkdir -p "$OUTPUT_DIR"

echo "{\"scenario\": \"$SCENARIO\", \"url\": \"$URL\", \"iterations\": $N, \"measurements\": [" > "$OUTPUT_DIR/${SCENARIO}.json"

for i in $(seq 1 $N); do
    echo "  Iteración $i/$N..."
    
    # Medición con curl -w para obtener timings detallados
    TIMING_FILE=$(mktemp)
    
    if [ -f "$COOKIE_FILE" ]; then
        RESPONSE=$(curl -s -w "\n%{time_namelookup},%{time_connect},%{time_appconnect},%{time_pretransfer},%{time_redirect},%{time_starttransfer},%{time_total},%{size_download},%{http_code},%{header:X-Perf-Id}" \
            -H "Cache-Control: no-cache, no-store, must-revalidate" \
            -H "Pragma: no-cache" \
            -b "$COOKIE_FILE" \
            -c "$COOKIE_FILE" \
            -o /dev/null "$URL" 2>&1)
    else
        RESPONSE=$(curl -s -w "\n%{time_namelookup},%{time_connect},%{time_appconnect},%{time_pretransfer},%{time_redirect},%{time_starttransfer},%{time_total},%{size_download},%{http_code},%{header:X-Perf-Id}" \
            -H "Cache-Control: no-cache, no-store, must-revalidate" \
            -H "Pragma: no-cache" \
            -c "$COOKIE_FILE" \
            -o /dev/null "$URL" 2>&1)
    fi
    
    # Parsear respuesta
    TIMINGS=$(echo "$RESPONSE" | tail -1)
    PERF_ID=$(echo "$RESPONSE" | tail -1 | cut -d',' -f10)
    
    # Guardar medición
    echo "    {\"iteration\": $i, \"timings\": \"$TIMINGS\", \"perf_id\": \"$PERF_ID\"}" >> "$OUTPUT_DIR/${SCENARIO}.json"
    
    if [ $i -lt $N ]; then
        echo "," >> "$OUTPUT_DIR/${SCENARIO}.json"
    fi
    
    # Pequeña pausa entre requests
    sleep 0.5
done

echo "]}" >> "$OUTPUT_DIR/${SCENARIO}.json"

echo "Medición completada: $OUTPUT_DIR/${SCENARIO}.json"
