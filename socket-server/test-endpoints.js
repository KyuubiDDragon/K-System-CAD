/**
 * Test-Skript für die Socket.io API-Endpunkte
 * 
 * Dieses Skript testet die API-Endpunkte für Benachrichtigungen
 * und stellt sicher, dass sie korrekt konfiguriert sind.
 */

require('dotenv').config();
const axios = require('axios');

// Konfiguration
const API_URL = process.env.API_URL || 'http://localhost:3001';
const API_KEY = process.env.API_KEY;
const TEST_USER_ID = 1; // ID des Testbenutzers

if (!API_KEY) {
  console.error('❌ API_KEY nicht in .env konfiguriert!');
  process.exit(1);
}

console.log('🔧 Starting API endpoint tests...');
console.log(`🔗 API URL: ${API_URL}`);
console.log(`👤 Test User ID: ${TEST_USER_ID}`);

// Headers für alle Anfragen
const headers = {
  'Content-Type': 'application/json',
  'X-API-Key': API_KEY
};

// Testdaten für Benachrichtigungen
const testData = {
  userId: TEST_USER_ID,
  title: 'Test Notification',
  message: 'This is a test notification from the API test script',
  sender_name: 'API Test Script'
};

// Funktion zum Testen eines Endpunkts
async function testEndpoint(endpoint, data) {
  console.log(`\n🧪 Testing endpoint: ${endpoint}`);
  try {
    const url = `${API_URL}${endpoint}`;
    console.log(`📡 Sending request to: ${url}`);
    console.log(`📦 Request data:`, data);

    const response = await axios.post(url, data, { headers });
    
    console.log(`✅ SUCCESS: Status code: ${response.status}`);
    console.log(`📊 Response data:`, response.data);
    return true;
  } catch (error) {
    console.error(`❌ ERROR: ${error.message}`);
    if (error.response) {
      console.error(`📊 Response status: ${error.response.status}`);
      console.error(`📊 Response data:`, error.response.data);
    }
    return false;
  }
}

// Hauptfunktion
async function runTests() {
  console.log('\n🚀 Starting endpoint tests...');
  
  // Test 1: Legacy Toast API (/api/toast)
  const legacyToastResult = await testEndpoint('/api/toast', testData);
  
  // Test 2: Legacy Notification API (/api/notification)
  const legacyNotifResult = await testEndpoint('/api/notification', testData);
  
  // Test 3: New Toast API (/api/notification/toast)
  const newToastResult = await testEndpoint('/api/notification/toast', testData);
  
  // Test Results Summary
  console.log('\n📋 TEST RESULTS SUMMARY:');
  console.log(`📌 Legacy Toast API (/api/toast): ${legacyToastResult ? '✅ PASS' : '❌ FAIL'}`);
  console.log(`📌 Legacy Notification API (/api/notification): ${legacyNotifResult ? '✅ PASS' : '❌ FAIL'}`);
  console.log(`📌 New Toast API (/api/notification/toast): ${newToastResult ? '✅ PASS' : '❌ FAIL'}`);
  
  // Final Result
  const allPassed = legacyToastResult && legacyNotifResult && newToastResult;
  console.log(`\n${allPassed ? '🎉 All tests PASSED!' : '⚠️ Some tests FAILED!'}`);
  
  // Überprüfen Sie die Socket.io-Serverprotokolle auf weitere Details
  console.log('\n⚠️ IMPORTANT: Check the socket-server logs to verify that notifications were correctly logged and emitted!');
}

// Run tests
runTests().catch(error => {
  console.error(`❌ Unhandled error: ${error.message}`);
  console.error(error.stack);
}); 