#!/bin/bash

# === CONFIGURATION ===
URL="https://rabbitmq.ma.prod.commerce.ci-aldi.com"
VHOST="de_queue"
SOURCE_QUEUE="optimize.capacity.error"
TARGET_QUEUE="event.retry.archive"
COUNT=5
DELAY=2
AUTH="<user>:<password>"

# === URL encode vhost ===
ENCODED_VHOST=$(python3 -c "import urllib.parse; print(urllib.parse.quote('''$VHOST'''))")

# === CHECK AND CREATE TARGET QUEUE IF MISSING ===
check_and_create_queue() {
  echo "🔍 Checking if target queue '$TARGET_QUEUE' exists..."

  STATUS_CODE=$(curl -k -s -o /dev/null -w "%{http_code}" -u $AUTH \
    "$URL/api/queues/$ENCODED_VHOST/$TARGET_QUEUE")

  if [ "$STATUS_CODE" -eq 404 ]; then
    echo "📦 Target queue does not exist. Creating '$TARGET_QUEUE'..."
    curl -k -s -u $AUTH -H "Content-Type: application/json" \
      -X PUT "$URL/api/queues/$ENCODED_VHOST/$TARGET_QUEUE" \
      -d '{"durable": true}'
    echo "✅ Target queue created."
  else
    echo "✅ Target queue exists."
  fi
}

# === BIND TARGET QUEUE TO amq.direct EXCHANGE ===
ensure_queue_binding() {
  echo "🔗 Ensuring binding to 'amq.direct' exchange..."

  # Check existing bindings to see if this one already exists
  BINDINGS=$(curl -k -s -u $AUTH "$URL/api/queues/$ENCODED_VHOST/$TARGET_QUEUE/bindings")
  IS_BOUND=$(echo "$BINDINGS" | jq -e \
    --arg key "$TARGET_QUEUE" \
    '.[] | select(.source=="amq.direct" and .routing_key==$key)' > /dev/null && echo "yes" || echo "no")

  if [ "$IS_BOUND" == "no" ]; then
    echo "🔧 Binding queue '$TARGET_QUEUE' to 'amq.direct' with routing key '$TARGET_QUEUE'..."
    curl -k -s -u $AUTH -H "Content-Type: application/json" \
      -X POST "$URL/api/bindings/$ENCODED_VHOST/e/amq.direct/q/$TARGET_QUEUE" \
      -d "{\"routing_key\": \"$TARGET_QUEUE\"}"
    echo "✅ Binding created."
  else
    echo "✅ Binding already exists."
  fi
}

# === FUNCTION TO FETCH AND MOVE MESSAGES ===
move_messages() {
  echo "Fetching up to $COUNT messages from '$SOURCE_QUEUE'..."

  RESPONSE=$(curl -k -s -u $AUTH -H "Content-Type: application/json" \
    -X POST "$URL/api/queues/$ENCODED_VHOST/$SOURCE_QUEUE/get" \
    -d "{
      \"count\": $COUNT,
      \"ackmode\": \"ack_requeue_true\",
      \"encoding\": \"auto\",
      \"truncate\": 50000
    }")

  MESSAGE_COUNT=$(echo "$RESPONSE" | jq length)

  if [ "$MESSAGE_COUNT" -eq 0 ]; then
    echo "No more messages to move. Exiting."
    exit 0
  fi

  echo "Moving $MESSAGE_COUNT messages to '$TARGET_QUEUE'..."

  echo "$RESPONSE" | jq -c '.[]' | while read -r row; do
    # Extract payload and encode in base64 safely
    RAW_PAYLOAD=$(echo "$row" | jq -r '.payload')
    BASE64_PAYLOAD=$(printf "%s" "$RAW_PAYLOAD" | base64)

    # Build safe publish JSON
    PUBLISH_JSON=$(jq -n \
      --arg rk "$TARGET_QUEUE" \
      --arg pl "$BASE64_PAYLOAD" \
      '{
        properties: {},
        routing_key: $rk,
        payload: $pl,
        payload_encoding: "base64"
      }')

    # POST to RabbitMQ
    curl -k -s -u $AUTH -H "Content-Type: application/json" \
      -X POST "$URL/api/exchanges/$ENCODED_VHOST/amq.direct/publish" \
      -d "$PUBLISH_JSON"
  done

  echo "Batch moved. Waiting $DELAY seconds..."
  sleep $DELAY
}

# === MAIN EXECUTION ===
check_and_create_queue
ensure_queue_binding

while true; do
  move_messages
done
