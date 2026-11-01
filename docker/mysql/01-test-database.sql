-- A separate database for the test suite (docker compose --profile test run --rm tests).
CREATE DATABASE IF NOT EXISTS worktime_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON worktime_test.* TO 'worktime'@'%';
