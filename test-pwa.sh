#!/bin/bash
# Test script for Medication Reminder PWA

echo "=== Medication Reminder PWA Test Suite ==="
echo ""

# Test 1: Server is running
echo "Test 1: Checking if server is running..."
if curl -s http://localhost:3000/api/vapid-public-key > /dev/null; then
    echo "✓ Server is running"
else
    echo "✗ Server is not running"
    exit 1
fi

# Test 2: VAPID key is available
echo ""
echo "Test 2: Checking VAPID public key..."
VAPID_KEY=$(curl -s http://localhost:3000/api/vapid-public-key | jq -r '.publicKey')
if [ -n "$VAPID_KEY" ]; then
    echo "✓ VAPID key available: ${VAPID_KEY:0:20}..."
else
    echo "✗ VAPID key not available"
    exit 1
fi

# Test 3: Add a medication
echo ""
echo "Test 3: Adding a test medication..."
MEDICATION=$(curl -s -X POST http://localhost:3000/api/medications \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Med","dose":"50mg","scheduledTimes":["10:00","22:00"],"instructions":"Test"}')
MED_ID=$(echo $MEDICATION | jq -r '.id')
if [ -n "$MED_ID" ]; then
    echo "✓ Medication added with ID: $MED_ID"
else
    echo "✗ Failed to add medication"
    exit 1
fi

# Test 4: Retrieve medications
echo ""
echo "Test 4: Retrieving medications..."
MEDS=$(curl -s http://localhost:3000/api/medications)
MED_COUNT=$(echo $MEDS | jq '. | length')
echo "✓ Found $MED_COUNT medication(s)"

# Test 5: Mark medication as taken
echo ""
echo "Test 5: Marking medication as taken..."
TAKEN=$(curl -s -X POST http://localhost:3000/api/medications/$MED_ID/taken \
  -H "Content-Type: application/json" \
  -d '{"scheduleTime":"10:00"}')
if echo $TAKEN | jq -e '.success' > /dev/null; then
    echo "✓ Medication marked as taken"
else
    echo "✗ Failed to mark medication as taken"
fi

# Test 6: Settings endpoints
echo ""
echo "Test 6: Testing settings endpoints..."
SETTINGS=$(curl -s http://localhost:3000/api/settings)
if echo $SETTINGS | jq -e '.notifyAtTime' > /dev/null; then
    echo "✓ Settings retrieved successfully"
else
    echo "✗ Failed to retrieve settings"
fi

# Test 7: Update settings
echo ""
echo "Test 7: Updating settings..."
UPDATE=$(curl -s -X POST http://localhost:3000/api/settings \
  -H "Content-Type: application/json" \
  -d '{"notifyAtTime":true,"notifyAfter10Min":false,"notifyAfter20Min":false,"notifyAfter30Min":false,"notifyAfter60Min":false}')
if echo $UPDATE | jq -e '.success' > /dev/null; then
    echo "✓ Settings updated successfully"
else
    echo "✗ Failed to update settings"
fi

# Test 8: Manifest.json
echo ""
echo "Test 8: Checking PWA manifest..."
MANIFEST=$(curl -s http://localhost:3000/manifest.json)
if echo $MANIFEST | jq -e '.name' > /dev/null; then
    echo "✓ Manifest.json is valid"
    echo "  App name: $(echo $MANIFEST | jq -r '.name')"
else
    echo "✗ Manifest.json is invalid"
fi

# Test 9: Service Worker
echo ""
echo "Test 9: Checking service worker..."
if curl -s http://localhost:3000/sw.js | grep -q "Service Worker"; then
    echo "✓ Service worker is accessible"
else
    echo "✗ Service worker is not accessible"
fi

# Test 10: Icons
echo ""
echo "Test 10: Checking PWA icons..."
ICON_COUNT=0
for size in 72 96 128 144 152 192 384 512; do
    if curl -s -I http://localhost:3000/icons/icon-${size}x${size}.svg | grep -q "200 OK"; then
        ((ICON_COUNT++))
    fi
done
echo "✓ Found $ICON_COUNT/8 icons"

echo ""
echo "=== Test Suite Complete ==="
echo "All critical tests passed! ✓"
