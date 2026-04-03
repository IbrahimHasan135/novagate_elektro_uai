# novagate_elektro_uai
Langkah setup:

Import schema.sql ke MySQL
Edit config/database.php - sesuaikan kredensial DB
Buka http://localhost/novagate_elektro_uai/
REST API endpoint: POST /api/v1/access-logs

Header: X-API-Key: <api_key>
Body: {"rfid_code": "...", "sent_at": "2026-04-03T10:00:00+07:00", "mac_address": "AA:BB:CC:DD:EE:FF"}

