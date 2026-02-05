# CommerceApi 🛒

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A robust, multi-tenant REST API for managing e-commerce operations, built with vanilla PHP and MySQL. CommerceApi provides comprehensive functionality for managing products, clients, sales, reports, and more, with built-in authentication, plan-based feature gating, and maintenance mode support.

## 📑 Table of Contents

- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [Project Structure](#-project-structure)
- [Installation & Setup](#-installation--setup)
- [Configuration](#-configuration)
- [API Endpoints](#-api-endpoints)
- [Authentication](#-authentication)
- [Client Authentication](#-client-authentication)
- [Security Implementation](#-security-implementation)
- [Request/Response Examples](#-requestresponse-examples)
- [Features & Plan System](#-features--plan-system)
- [Maintenance Mode](#-maintenance-mode)
- [Error Handling](#-error-handling)
- [Logging System](#-logging-system)
- [Security Considerations](#-security-considerations)
- [Development](#-development)
- [Testing](#-testing)
- [Roadmap](#-roadmap)
- [Contributing](#-contributing)
- [License](#-license)
- [Contact](#-contact)

## ✨ Features

- **🏢 Multi-Tenant Architecture**: Isolated database per tenant for data security
- **📊 Plan-Based Feature Gating**: Control feature access based on subscription plans
- **🔐 HTTP Basic Authentication**: Secure API access with encrypted credentials
- **🔒 Client Authentication (API Key + HMAC-SHA256)**: Cryptographic request signing for tamper-proof API access
- **⏱️ Rate Limiting**: 60 requests/minute, 1000 requests/hour per client
- **📊 Request Audit Logging**: Complete tracking of all API requests with signature validation
- **🛡️ Replay Attack Prevention**: Timestamp validation (±5 minutes)
- **📦 Product Management**: Full CRUD operations with optional stock control
- **👥 Client Management**: Manage clients with nested sales resources
- **💰 Sales Management**: Track and manage sales transactions
- **📈 Reports**: Generate sales and other business reports with date ranges
- **⚙️ Configuration Management**: Dynamic API settings and configurations
- **🔧 Maintenance Mode**: Control API availability with debug key override
- **📝 Comprehensive Logging**: Track errors, info, and execution traces
- **🔑 Cryptographic Keys Management**: Secure key storage and retrieval
- **💊 Health Check Endpoint**: Monitor API status

### Security 🏆

CommerceApi implements enterprise-grade security measures:

✅ **Multi-Layer Authentication**
- Client authentication (API Key + HMAC-SHA256 signatures)
- User authentication (Basic Auth with bcrypt password hashing)

✅ **Request Integrity & Protection**
- Cryptographic request signing prevents tampering
- Timestamp validation prevents replay attacks (±5 minute window)
- Rate limiting (60/min, 1000/hour per client)

✅ **Complete Audit Trail**
- All requests logged with signature validation status
- Failed authentication attempts tracked
- IP address and performance metrics recorded

✅ **Data Security**
- Email encryption with AES-256-GCM
- SQL injection prevention with prepared statements
- Multi-tenant database isolation

✅ **API Protection**
- Rate limiting prevents brute force and abuse
- Plan-based feature gating
- Maintenance mode with debug key override

## 🛠 Technology Stack

- **Backend**: PHP 7.4+ (Vanilla PHP, no framework)
- **Database**: MySQL with PDO for prepared statements
- **Dependencies**: 
  - Composer (dependency management)
  - vlucas/phpdotenv (environment variable management)
- **Server**: Apache or Nginx with URL rewriting
- **Architecture**: MVC pattern (Models, Controllers)

## 📂 Project Structure

```
CommerceApi/
├── .env.example              # Environment configuration template
├── .htaccess                 # Apache URL rewriting rules
├── bootstrap.php             # Application bootstrap and autoloader
├── index.php                 # Main entry point
├── composer.json             # Composer dependencies
├── composer.lock             # Locked dependency versions
├── vendor/                   # Composer dependencies (gitignored)
└── Src/
    ├── Controller/           # Request handlers
    │   ├── ApiController.php       # Main API routing controller
    │   ├── ClientController.php    # Client operations
    │   ├── ConfigController.php    # Configuration management
    │   ├── LogController.php       # Logging (disabled externally)
    │   ├── PlanController.php      # Plan and features
    │   ├── productController.php   # Product operations
    │   ├── ReportController.php    # Report generation
    │   ├── SaleController.php      # Sales operations
    │   └── SettingsController.php  # API settings
    ├── Middleware/           # Client Authentication Middleware
    │   ├── ClientAuthMiddleware.php       
    ├── Model/                # Data layer
    │   ├── ApiClientModel.php         # API client credentials
    │   ├── ClientModel.php         # Client data operations
    │   ├── ConfigModel.php         # Configuration data
    │   ├── LogModel.php            # Logging operations
    │   ├── PlanModel.php           # Plan feature mapping
    │   ├── productModel.php        # Product data operations
    │   ├── ReportModel.php         # Report data generation
    │   ├── SaleModel.php           # Sales data operations
    │   ├── SettingsModel.php       # Settings data
    │   └── UserModel.php           # Authentication & user data
    ├── Scripts/            
    │   ├── ...         # Setup and debug scripts   
    ├── System/                # Data layer
        ├── DatabaseConnector.php         # DB Connection
        ├── EncryptionService.php         # Handles encryption
        ├── RateLimiter.php         # Handles rate limiting
```

### Architecture Overview

The application follows an **MVC (Model-View-Controller)** architecture:

- **Controllers**: Handle HTTP requests, validate inputs, and orchestrate business logic
- **Middleware**: Middleware for client auth
- **Models**: Interact with the database and contain business logic
- **System**: Core infrastructure components (database connections, utilities)

The API uses a **multi-tenant architecture** where:
1. Initial authentication occurs against a central authentication database
2. Upon successful authentication, the API connects to the tenant-specific database
3. All subsequent operations are performed in the tenant's isolated database

## 🚀 Installation & Setup

### Prerequisites

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Composer**: Latest version
- **Web Server**: Apache or Nginx
- **Extensions**: 
  - PDO
  - PDO_MySQL
  - OpenSSL (for encryption)

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/raulpellizzer/CommerceApi.git
   cd CommerceApi
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` with your database credentials:
   ```env
   # Database Config
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=
   DB_USER=
   DB_PASSWORD=

   # Encryption Keys
   API_DEBUG_KEY=
   ENCRYPTION_KEY=
   API_ENCRYPTION_KEY=

   # Client Authentication
   # Generate with: php scripts/generate_api_credentials.php
   WPF_APP_API_KEY=
   WPF_APP_API_SECRET=

   # Rate Limiting
   RATE_LIMIT_ENABLED=true
   RATE_LIMIT_STORAGE=database  # Options: database, redis, apcu
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   REDIS_PASSWORD=

   # Security
   SIGNATURE_MAX_AGE_SECONDS=300  # 5 minutes
   REQUEST_AUDIT_ENABLED=true
   DEVICE_REGISTRATION_REQUIRED=false

   # Production Settings
   APP_ENV=development  # development, staging, production
   LOG_LEVEL=warning   # debug, info, warning, error
   ```

4. **Database Setup**
   
   Create the authentication database:
   ```sql
   CREATE DATABASE commerce_api_auth;
   ```
   
   Create necessary tables (refer to your schema):
   - `user` table for authentication
   - `PlanFeature` and `Feature` tables for plan management
   - `Logs` table for logging
   
   Create tenant-specific databases for each client.

5. **Configure URL Rewriting**
   
   **For Apache**: The `.htaccess` file is already configured:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_URI} !=/index.php
   RewriteRule . index.php [L]
   ```
   
   **For Nginx**: Add this to your server block:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

6. **Set Permissions**
   ```bash
   chmod 755 /path/to/CommerceApi
   chmod 644 .env
   ```

7. **Setup Client Authentication**
   
   Generate API credentials for your client applications:
   ```bash
   cd src/Scripts
   php generate_api_credentials.php
   ```
   
   Follow the interactive prompts to create credentials. Then add the generated keys to your `.env` file:
   ```env
   # Client Authentication (add these)
   WPF_APP_API_KEY=your_generated_api_key_here
   WPF_APP_API_SECRET=your_generated_api_secret_here
   ```
   
   ⚠️ **Important**: Keep API secrets secure. Never commit them to version control or expose them in client-side code.

8. **Test Installation**
   ```bash
   curl http://localhost/CommerceApi/
   ```
   
   Expected response:
   ```json
   {
     "status": "200",
     "message": "API is up and running"
   }
   ```

## ⚙️ Configuration

### Environment Variables (.env)

The `.env.example` file provides a template for configuration:

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | MySQL server hostname | `localhost` |
| `DB_PORT` | MySQL server port | `3306` |
| `DB_NAME` | Authentication database name | `commerce_api_auth` |
| `DB_USER` | Database username | `root` |
| `DB_PASSWORD` | Database password | `yourpassword` |
| `ENCRYPTION_KEY` | 32-character key for AES-256-GCM encryption | `your32characterencryptionkey123` |
| `API_DEBUG_KEY` | Debug key for maintenance mode override | `debug-secret-key` |

### Security Notes

- **Never commit `.env` to version control** (it's in `.gitignore`)
- Use strong, unique values for `ENCRYPTION_KEY` and `API_DEBUG_KEY`
- Restrict database user permissions to only what's necessary
- Use HTTPS in production to protect Basic Auth credentials

## 🔌 API Endpoints

**Base URL Pattern**: `/CommerceApi/{resource}`

**Authentication**: All endpoints require HTTP Basic Authentication

### Available Endpoints

#### Health Check
- **GET** `/CommerceApi/` - Check if API is up and running

#### Products
- **GET** `/CommerceApi/products?stockcontrol=true` - Get all products
- **POST** `/CommerceApi/products` - Create new product(s)
- **PUT** `/CommerceApi/products` - Update product(s)
- **DELETE** `/CommerceApi/products` - Delete product(s)

**Parameters**:
- `stockcontrol` (optional): Enable stock control features

#### Clients
- **GET** `/CommerceApi/clients` - Get all clients
- **POST** `/CommerceApi/clients` - Create new client(s)
- **PUT** `/CommerceApi/clients` - Update client(s)
- **DELETE** `/CommerceApi/clients` - Delete client(s)
- **GET** `/CommerceApi/clients/getclientsales?clientid={id}` - Get sales for specific client
- **GET** `/CommerceApi/clients/getclientsalesdetails?saleid={id}` - Get sales details

**Parameters**:
- `clientid` (required for nested resources): Client identifier
- `saleid` (required for sales details): Sale identifier

#### Sales
- **GET** `/CommerceApi/sales` - Get all sales
- **POST** `/CommerceApi/sales` - Create new sale(s)
- **PUT** `/CommerceApi/sales` - Update sale(s)
- **DELETE** `/CommerceApi/sales` - Delete sale(s)

#### Reports
- **GET** `/CommerceApi/reports?reporttype={type}&begindate={date}&enddate={date}` - Generate reports

**Parameters** (all required):
- `reporttype`: Type of report (e.g., "Sales")
- `begindate`: Start date (format: YYYY-MM-DD)
- `enddate`: End date (format: YYYY-MM-DD)

**Example**: `/CommerceApi/reports?reporttype=Sales&begindate=2025-05-04&enddate=2025-05-12`

#### Configurations
- **GET** `/CommerceApi/configs` - Get configurations
- **PUT** `/CommerceApi/configs` - Update configurations

#### Plans
- **GET** `/CommerceApi/plan` - Get plan data and available features

#### Cryptographic Keys
- **GET** `/CommerceApi/keys` - Get cryptographic keys

#### Logs
- ⚠️ **External access disabled** - Internal use only

## 🔐 Authentication

CommerceApi uses **HTTP Basic Authentication** for all API requests.

### How It Works

1. Credentials are sent with each request using the `Authorization` header
2. Username should be the user's email address
3. Password is verified against a hashed value in the database
4. Email is encrypted using AES-256-GCM encryption
5. Email hash (SHA-256) is used for lookup

### Making Authenticated Requests

**cURL Example**:
```bash
curl -u "user@example.com:password" \
  http://localhost/CommerceApi/products
```

**JavaScript Example**:
```javascript
const username = 'user@example.com';
const password = 'userpassword';
const credentials = btoa(`${username}:${password}`);

fetch('http://localhost/CommerceApi/products', {
  headers: {
    'Authorization': `Basic ${credentials}`
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### Authentication Validation

The API checks:
- ✅ User exists (via email hash lookup)
- ✅ Password matches (bcrypt verification)
- ✅ User is active (`is_active = 1`)
- ✅ User is verified (`is_verified = 1`)

### Unauthorized Response

**HTTP 401 Unauthorized**:
```json
{
  "status": "401",
  "message": "Unauthorized"
}
```

## 🔒 Client Authentication

CommerceApi implements **enterprise-grade client authentication** using HMAC-SHA256 signatures to ensure request integrity and prevent tampering. This is in addition to user authentication and provides a robust multi-layer security model.

### Authentication Layers

CommerceApi uses a **two-layer authentication system**:

#### Layer 1: Client Authentication (API Key + HMAC Signature)
- **Purpose**: Verifies the request comes from an authorized application
- **Implementation**: API Key identification + HMAC-SHA256 signature validation
- **Headers Required**:
  - `X-API-Key`: Identifies the client application
  - `X-Signature`: HMAC-SHA256 signature of the request
  - `X-Timestamp`: Unix timestamp (validated within ±5 minutes)

#### Layer 2: User Authentication (Basic Auth)
- **Purpose**: Identifies which user is making the request
- **Implementation**: Email/password with bcrypt hashing
- **Header**: `Authorization: Basic {base64(email:password)}`

### How HMAC Signature Works

The signature ensures request integrity by signing a message containing all critical request components:

**Message Format**:
```
{METHOD}|{URI}|{BODY}|{TIMESTAMP}
```

**PHP Example**:
```php
// Request components
$method = 'GET';                           // HTTP method
$uri = '/CommerceApi/products';            // Full URI path (with query string if present)
$body = '';                                // Request body (empty for GET)
$timestamp = time();                       // Current Unix timestamp

// Build message
$message = "{$method}|{$uri}|{$body}|{$timestamp}";

// Generate HMAC-SHA256 signature
$signature = hash_hmac('sha256', $message, $apiSecret);
```

**Important Notes**:
- The URI must include the full path and query string (e.g., `/CommerceApi/products?stockcontrol=true`)
- For POST/PUT requests, the body must be included in the signature calculation
- The timestamp must be within ±5 minutes of server time to prevent replay attacks
- The API secret is never transmitted over the network

### Request Example with Client Authentication

**GET Request**:
```bash
# Generate timestamp
TIMESTAMP=$(date +%s)

# Generate signature (replace with your secret)
API_SECRET="your_api_secret_here"
METHOD="GET"
URI="/CommerceApi/products"
BODY=""
MESSAGE="${METHOD}|${URI}|${BODY}|${TIMESTAMP}"
SIGNATURE=$(echo -n "$MESSAGE" | openssl dgst -sha256 -hmac "$API_SECRET" | cut -d' ' -f2)

# Make request
curl -X GET https://yourapi.com/CommerceApi/products \
  -H "X-API-Key: abc123..." \
  -H "X-Signature: $SIGNATURE" \
  -H "X-Timestamp: $TIMESTAMP" \
  -u "user@example.com:password"
```

**POST Request with Body**:
```bash
# Request body
BODY='{"name":"New Product","price":19.99}'

# Generate timestamp
TIMESTAMP=$(date +%s)

# Generate signature
API_SECRET="your_api_secret_here"
METHOD="POST"
URI="/CommerceApi/products"
MESSAGE="${METHOD}|${URI}|${BODY}|${TIMESTAMP}"
SIGNATURE=$(echo -n "$MESSAGE" | openssl dgst -sha256 -hmac "$API_SECRET" | cut -d' ' -f2)

# Make request
curl -X POST https://yourapi.com/CommerceApi/products \
  -H "Content-Type: application/json" \
  -H "X-API-Key: abc123..." \
  -H "X-Signature: $SIGNATURE" \
  -H "X-Timestamp: $TIMESTAMP" \
  -u "user@example.com:password" \
  -d "$BODY"
```

### Authentication Failure Responses

**Invalid API Key** (403 Forbidden):
```json
{
  "status": "403",
  "message": "Invalid API key"
}
```

**Invalid Signature** (403 Forbidden):
```json
{
  "status": "403",
  "message": "Invalid request signature"
}
```

**Expired Timestamp** (403 Forbidden):
```json
{
  "status": "403",
  "message": "Request timestamp expired"
}
```

## 🛡️ Security Implementation

This section documents the complete security infrastructure including client authentication setup, rate limiting, and audit logging.

### Setup - Generate API Credentials

Use the provided script to generate secure API credentials for your client applications:

```bash
cd src/Scripts
php generate_api_credentials.php
```

**Interactive Prompts**:
```
Enter client details:
---------------------------------------------
Client Name (e.g., 'WPF Desktop App'): My Application
Client Version (default: 1.0.0): 1.0.0
Client Type [wpf_desktop/mobile_ios/mobile_android/web] (default: wpf_desktop): wpf_desktop
Rate Limit (per minute, default: 60): 60
Rate Limit (per hour, default: 1000): 1000
```

**Output Example**:
```
============================================
API CLIENT CREDENTIALS GENERATED
============================================

Client Details:
  Name: My Application
  Version: 1.0.0
  Type: wpf_desktop

Credentials (SAVE THESE SECURELY):
---------------------------------------------
API Key:    a1b2c3d4e5f6g7h8_1770298500
API Secret: 9i8j7k6l5m4n3o2p1q0r9s8t7u6v5w4x3y2z1a0b9c8d7e6f5g4h3i2j1k0l9m8n7o6p5q4r3s2t1u0v9w8x

Rate Limits:
  Per Minute: 60 requests
  Per Hour: 1000 requests
```

**Add to .env File**:
```env
# Client Authentication
WPF_APP_API_KEY=a1b2c3d4e5f6g7h8_1770298500
WPF_APP_API_SECRET=9i8j7k6l5m4n3o2p1q0r9s8t7u6v5w4x3y2z1a0b9c8d7e6f5g4h3i2j1k0l9m8n7o6p5q4r3s2t1u0v9w8x
```

⚠️ **Security Warning**: Store API secrets securely. Never commit them to version control or expose them in client-side code.

### Database Schema - api_clients Table

The API client credentials are stored in the authentication database:

```sql
CREATE TABLE api_clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    api_secret VARCHAR(255) NOT NULL,
    client_version VARCHAR(50),
    client_type VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    rate_limit_per_minute INT DEFAULT 60,
    rate_limit_per_hour INT DEFAULT 1000,
    last_request_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_active (is_active)
);
```

**Key Fields**:
- `api_key`: Unique identifier for the client (transmitted with requests)
- `api_secret`: Secret key for HMAC signature generation (never transmitted)
- `is_active`: Enable/disable client access without deleting the record
- `rate_limit_per_minute` / `rate_limit_per_hour`: Per-client rate limits

### Rate Limiting Configuration

Rate limiting is enforced **per API client** (not per user) to prevent API abuse and protect server resources.

**Default Limits**:
- **60 requests per minute** (1-minute sliding window)
- **1000 requests per hour** (1-hour sliding window)

**Implementation**:
- Database-based request tracking (with optional Redis support)
- Sliding window algorithm for accurate rate limiting
- Per-client enforcement using API key
- Automatic cleanup of old records

**Rate Limit Headers** (included in all responses):
```
X-RateLimit-Limit-Minute: 60
X-RateLimit-Remaining-Minute: 45
X-RateLimit-Limit-Hour: 1000
X-RateLimit-Remaining-Hour: 850
```

**Rate Limit Exceeded Response** (429 Too Many Requests):
```json
{
  "status": "429",
  "message": "Rate limit exceeded. Try again in 42 seconds."
}
```

**Custom Rate Limits**:
Each client can have custom rate limits set in the `api_clients` table. Update the values directly:
```sql
UPDATE api_clients 
SET rate_limit_per_minute = 120, 
    rate_limit_per_hour = 2000 
WHERE api_key = 'your_api_key';
```

### Request Audit Logging

All API requests are logged to the `api_request_log` table for security auditing, compliance, and monitoring.

**Database Schema**:
```sql
CREATE TABLE api_request_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    api_key VARCHAR(255),
    authenticated_user VARCHAR(255),
    endpoint VARCHAR(500),
    http_method VARCHAR(10),
    http_status_code INT,
    request_ip VARCHAR(45),
    signature_valid TINYINT(1),
    timestamp_valid TINYINT(1),
    response_time_ms INT,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_timestamp (created_at),
    INDEX idx_signature_valid (signature_valid),
    INDEX idx_status (http_status_code)
);
```

**Logged Information**:
- **api_key**: Which client made the request
- **authenticated_user**: Which user was authenticated (from Basic Auth)
- **endpoint**: Full URI path and query string
- **http_method**: GET, POST, PUT, DELETE
- **http_status_code**: Response status (200, 403, 429, etc.)
- **request_ip**: Source IP address
- **signature_valid**: Whether HMAC signature was valid (1=valid, 0=invalid)
- **timestamp_valid**: Whether timestamp was within acceptable range
- **response_time_ms**: Request processing time in milliseconds

**Monitoring Queries**:

Find failed authentication attempts:
```sql
SELECT api_key, endpoint, COUNT(*) as failed_attempts
FROM api_request_log
WHERE signature_valid = 0 
  AND created_at > NOW() - INTERVAL 1 HOUR
GROUP BY api_key, endpoint
ORDER BY failed_attempts DESC;
```

Track suspicious activity (multiple failed signatures):
```sql
SELECT api_key, request_ip, COUNT(*) as failures,
       MIN(created_at) as first_attempt,
       MAX(created_at) as last_attempt
FROM api_request_log
WHERE signature_valid = 0
  AND created_at > NOW() - INTERVAL 24 HOUR
GROUP BY api_key, request_ip
HAVING failures > 10
ORDER BY failures DESC;
```

Monitor rate limit violations:
```sql
SELECT api_key, endpoint, COUNT(*) as rate_limit_hits
FROM api_request_log
WHERE http_status_code = 429
  AND created_at > NOW() - INTERVAL 1 DAY
GROUP BY api_key, endpoint
ORDER BY rate_limit_hits DESC;
```

Performance monitoring:
```sql
SELECT endpoint, 
       AVG(response_time_ms) as avg_ms,
       MAX(response_time_ms) as max_ms,
       COUNT(*) as requests
FROM api_request_log
WHERE created_at > NOW() - INTERVAL 1 HOUR
  AND http_status_code = 200
GROUP BY endpoint
ORDER BY avg_ms DESC;
```

### Testing Scripts

The `src/Scripts/` directory contains comprehensive test scripts for validating the security implementation:

**Valid Authentication Test**:
```bash
php test_client_auth_valid.php
```
Tests a properly signed request. Expected result: **200 OK**

**Invalid API Key Test**:
```bash
php test_client_auth_invalid_key.php
```
Tests with a non-existent API key. Expected result: **403 Forbidden** with "Invalid API key"

**Invalid Signature Test**:
```bash
php test_client_auth_invalid_signature.php
```
Tests with an incorrect HMAC signature. Expected result: **403 Forbidden** with "Invalid request signature"

**Expired Timestamp Test**:
```bash
php test_client_auth_expired.php
```
Tests with a timestamp outside the ±5 minute window. Expected result: **403 Forbidden** with "Request timestamp expired"

**Rate Limiting Test**:
```bash
php test_rate_limiting.php
```
Sends multiple rapid requests to test rate limiting. Expected result: **429 Too Many Requests** after exceeding limits

**POST Request Test**:
```bash
php test_post_request.php
```
Tests POST requests with body content in signature. Expected result: **200/201** with proper signature validation

**Query String Test**:
```bash
php test_query_string.php
```
Tests that query parameters are properly included in signature. Expected result: **200 OK** with proper validation

**All Tests**:
```bash
# Run all authentication tests
cd src/Scripts
php test_client_auth_valid.php
php test_client_auth_invalid_key.php
php test_client_auth_invalid_signature.php
php test_client_auth_expired.php
php test_post_request.php
php test_query_string.php
php test_rate_limiting.php
```

## 📨 Request/Response Examples

### GET Request - List Products

**Request**:
```bash
curl -u "user@example.com:password" \
  -X GET \
  http://localhost/CommerceApi/products
```

**Response** (200 OK):
```json
{
  "status": "200",
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "price": 29.99,
      "stock": 100
    }
  ]
}
```

### POST Request - Create Product

**Request**:
```bash
curl -u "user@example.com:password" \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"name":"New Product","price":19.99,"stock":50}' \
  http://localhost/CommerceApi/products
```

**Response** (201 Created):
```json
{
  "status": "201",
  "message": "Product created successfully",
  "data": {
    "id": 2
  }
}
```

### PUT Request - Update Product

**Request**:
```bash
curl -u "user@example.com:password" \
  -X PUT \
  -H "Content-Type: application/json" \
  -d '{"id":2,"name":"Updated Product","price":24.99}' \
  http://localhost/CommerceApi/products
```

**Response** (200 OK):
```json
{
  "status": "200",
  "message": "Product updated successfully"
}
```

### DELETE Request - Remove Product

**Request**:
```bash
curl -u "user@example.com:password" \
  -X DELETE \
  -H "Content-Type: application/json" \
  -d '{"id":2}' \
  http://localhost/CommerceApi/products
```

**Response** (200 OK):
```json
{
  "status": "200",
  "message": "Product deleted successfully"
}
```

### Feature Gating Response

When a feature is not available in the user's plan:

**Response** (403 Forbidden):
```json
{
  "status": "403",
  "message": "Feature not available in your current plan"
}
```

### Reports Example

**Request**:
```bash
curl -u "user@example.com:password" \
  "http://localhost/CommerceApi/reports?reporttype=Sales&begindate=2025-01-01&enddate=2025-01-31"
```

**Response** (200 OK):
```json
{
  "status": "200",
  "data": {
    "reportType": "Sales",
    "period": {
      "from": "2025-01-01",
      "to": "2025-01-31"
    },
    "summary": {
      "totalSales": 15000.00,
      "transactions": 45
    }
  }
}
```

## 🎯 Features & Plan System

CommerceApi implements a **plan-based feature gating system** that controls access to features based on the tenant's subscription plan.

### Plan Types

Plans are hierarchical, where higher-tier plans include all features from lower tiers:

- **Free Plan** (Plan ID: 1) - Basic features
- **Standard Plan** (Plan ID: 2) - Free features + additional capabilities
- **Premium Plan** (Plan ID: 3) - All features

### How It Works

1. Each tenant is assigned a `plan_type` in the user database
2. Features are mapped to plans in the `PlanFeature` table
3. When a request is made, the API checks if the feature is available
4. If not available, a **403 Forbidden** response is returned

### Feature Availability Check

**Endpoint**: `GET /CommerceApi/plan`

**Response**:
```json
{
  "PlanFeatures": [
    {
      "PlanId": 1,
      "FeatureId": 1,
      "FeatureName": "BasicProducts"
    },
    {
      "PlanId": 2,
      "FeatureId": 2,
      "FeatureName": "StockControl"
    }
  ],
  "UserPlanType": "2",
  "UserSubscriptionData": {
    "CreatedDate": "2025-01-15",
    "TenantName": "Example Company"
  },
  "Status": "200"
}
```

### Example: Feature Restriction

If a user with a Free plan tries to access a Premium feature:

```bash
curl -u "freeuser@example.com:password" \
  http://localhost/CommerceApi/products?stockcontrol=true
```

**Response** (403 Forbidden):
```json
{
  "status": "403",
  "message": "Stock control feature not available in your current plan"
}
```

## 🔧 Maintenance Mode

The API supports a maintenance mode that can disable access while allowing developers to test using a debug key.

### Enabling/Disabling the API

API availability is controlled via the `IsApiOnline` setting in the configurations table.

**Check API Status**:
```bash
curl -u "admin@example.com:password" \
  http://localhost/CommerceApi/configs
```

**Enable/Disable API**:
```bash
curl -u "admin@example.com:password" \
  -X PUT \
  -H "Content-Type: application/json" \
  -d '{"IsApiOnline":0}' \
  http://localhost/CommerceApi/configs
```

### Debug Key Override

During maintenance, developers can bypass the check using the debug key:

```bash
curl -u "dev@example.com:password" \
  "http://localhost/CommerceApi/products?debug-key=your_debug_key"
```

**Configuration**:
Set `API_DEBUG_KEY` in your `.env` file:
```env
API_DEBUG_KEY=my-secret-debug-key-12345
```

### Maintenance Response

When the API is in maintenance mode without a valid debug key:

**Response** (503 Service Unavailable):
```json
{
  "status": "503",
  "message": "API is currently unavailable"
}
```

## ⚠️ Error Handling

CommerceApi uses standard HTTP status codes and returns errors in a consistent JSON format.

### HTTP Status Codes

| Code | Status | Description |
|------|--------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request parameters or data |
| 401 | Unauthorized | Authentication failed or missing |
| 403 | Forbidden | Feature not available in current plan or invalid client authentication |
| 404 | Not Found | Resource or endpoint not found |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error occurred |
| 503 | Service Unavailable | API in maintenance mode |

### Error Response Format

All errors follow this structure:

```json
{
  "status": "4xx or 5xx",
  "message": "Human-readable error description"
}
```

### Common Errors and Troubleshooting

#### 401 Unauthorized
**Cause**: Invalid credentials, inactive user, or unverified account

**Solutions**:
- Verify email and password are correct
- Check if user account is active (`is_active = 1`)
- Ensure user is verified (`is_verified = 1`)

#### 400 Bad Request
**Cause**: Missing required parameters or invalid data format

**Example**:
```json
{
  "status": "400",
  "message": "Report types or dates not specified"
}
```

**Solutions**:
- Check API documentation for required parameters
- Validate JSON payload format
- Ensure all required fields are present

#### 403 Forbidden
**Cause**: Feature not available in current plan

**Solutions**:
- Upgrade subscription plan
- Check feature availability with `GET /CommerceApi/plan`

#### 404 Not Found
**Cause**: Invalid endpoint or resource doesn't exist

**Solutions**:
- Verify endpoint URL matches documentation
- Check if resource exists in database

#### 500 Internal Server Error
**Cause**: Database connection issues, query errors, or server-side bugs

**Solutions**:
- Check database connection settings in `.env`
- Review logs for stack traces
- Ensure database schema is correct

#### 503 Service Unavailable
**Cause**: API in maintenance mode

**Solutions**:
- Wait for maintenance to complete
- Use debug key if you're a developer: `?debug-key=your_key`

#### 429 Too Many Requests
**Cause**: Rate limit exceeded for the API client

**Response Format**:
```json
{
  "status": "429",
  "message": "Rate limit exceeded. Try again in 42 seconds."
}
```

**Rate Limit Headers**:
All responses include rate limit information:
```
X-RateLimit-Limit-Minute: 60
X-RateLimit-Remaining-Minute: 0
X-RateLimit-Limit-Hour: 1000
X-RateLimit-Remaining-Hour: 450
```

**Solutions**:
- Wait for the rate limit window to reset (shown in error message)
- Implement exponential backoff in your client application
- Contact administrator for higher rate limits if needed
- Check the `X-RateLimit-*` headers to track usage

## 📝 Logging System

CommerceApi includes a comprehensive logging system that tracks errors, information, and execution traces.

### What Gets Logged

The logging system captures:
- **Execution Time**: Timestamp of the event
- **File**: Source file where the log originated
- **Function**: Function or method name
- **Message**: Descriptive log message
- **Arguments**: Function arguments (if applicable)
- **Stack Trace**: Full stack trace for errors
- **Type**: Log type (info, error, warning)
- **Category**: Log category for filtering

### Log Storage

Logs are stored in the `Logs` table in the tenant database:

```sql
CREATE TABLE Logs (
  Id INT PRIMARY KEY AUTO_INCREMENT,
  ExecutionTime DATETIME,
  File VARCHAR(255),
  Function VARCHAR(255),
  Message TEXT,
  Args TEXT,
  StackTrace TEXT,
  Type VARCHAR(50),
  Category VARCHAR(100)
);
```

### External Access

⚠️ **The `/logs` endpoint is disabled for external access** for security reasons. Logs can only be accessed directly from the database or through internal system tools.

### Log Categories

Common log categories include:
- **Authentication**: Login attempts, auth failures
- **Database**: Query errors, connection issues
- **API**: Request processing, validation errors
- **Business Logic**: Feature gating, plan checks

## 🔒 Security Considerations

CommerceApi implements multiple security measures to protect data and prevent attacks.

### 1. Authentication Security

- **HTTP Basic Auth over HTTPS**: Basic authentication should only be used over HTTPS in production
- **Password Hashing**: Passwords are hashed using bcrypt (`password_hash()` / `password_verify()`)
- **Email Encryption**: User emails are encrypted using AES-256-GCM
- **Email Hashing**: SHA-256 hashes used for email lookups

⚠️ **Production Recommendation**: Implement HTTPS/SSL certificates for production deployments.

### 2. SQL Injection Prevention

All database queries use **prepared statements with PDO**:

```php
$statement = $this->dbConnection->prepare(
  'SELECT * FROM products WHERE id = :id'
);
$statement->execute(['id' => $productId]);
```

✅ **Never** use string concatenation for SQL queries.

### 3. Input Sanitization

- Validate and sanitize all user inputs
- Check required parameters before processing
- Validate data types and formats

### 4. Multi-Tenant Isolation

- Each tenant has an isolated database
- Tenant database is determined from authenticated user
- Cross-tenant data access is prevented at the architecture level

### 5. Environment Variables

- Sensitive data stored in `.env` (never committed to git)
- Encryption keys, passwords, and debug keys protected
- `.env` is in `.gitignore` by default

### 6. CORS Configuration

CORS headers are configured in `index.php`:

```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
```

⚠️ **Production Recommendation**: Restrict `Access-Control-Allow-Origin` to specific domains instead of `*`.

### 7. Feature Access Control

Plan-based feature gating prevents unauthorized access to premium features.

### 8. Client Authentication (HMAC Signatures)

- **HMAC-SHA256 signatures** ensure request integrity and authenticity
- **Timestamp validation** prevents replay attacks (±5 minute window)
- **API secrets never transmitted** over the network
- **Signature includes** HTTP method, URI, body, and timestamp
- **Per-client authentication** separate from user authentication
- **Failed attempts logged** for security monitoring

### 9. Rate Limiting

- **Prevents brute force attacks** and credential stuffing
- **Limits API abuse** and resource exhaustion
- **Database or Redis-based** tracking with sliding windows
- **Per-client enforcement** (60/min, 1000/hour default)
- **Configurable limits** per client in database
- **Automatic cleanup** of old tracking records

### 10. Audit Logging

- **All requests logged** with validation status
- **Failed signature attempts tracked** for security analysis
- **Monitor for suspicious patterns** (multiple failures from same IP/client)
- **Regulatory compliance support** with complete audit trail
- **Performance metrics** tracked (response times)
- **IP address logging** for geographic analysis

### Security Best Practices

1. **Use HTTPS in production** - Essential for Basic Auth and protecting API keys
2. **Restrict CORS origins** - Don't use `*` in production
3. **Regular updates** - Keep PHP and dependencies updated
4. **Strong credentials** - Use strong passwords and encryption keys
5. **Database permissions** - Use least-privilege database users
6. **Monitor logs** - Regularly review audit logs for suspicious activity
7. **Backup data** - Regular database backups
8. **Rotate API secrets** - Periodically regenerate API credentials
9. **Review rate limits** - Adjust based on legitimate usage patterns
10. **Alert on anomalies** - Set up monitoring for repeated auth failures

## 💻 Development

### Adding New Endpoints

#### 1. Create a Model

Create a new model in `src/model/`:

```php
<?php
namespace Src\Model;

class NewResourceModel {
    private $dbConnection;
    
    public function __construct($dbConnection) {
        $this->dbConnection = $dbConnection;
    }
    
    public function getData() {
        // Implement data retrieval
    }
}
```

#### 2. Create a Controller

Create a controller in `src/controller/`:

```php
<?php
namespace Src\Controller;
use Src\Model\NewResourceModel;

class NewResourceController {
    private $requestMethod;
    private $model;
    
    public function __construct($requestMethod, $dbConnection) {
        $this->requestMethod = $requestMethod;
        $this->model = new NewResourceModel($dbConnection);
    }
    
    public function processRequest() {
        switch ($this->requestMethod) {
            case 'GET':
                return $this->model->getData();
            default:
                return json_encode([
                    'status' => '400',
                    'message' => 'Invalid request method'
                ]);
        }
    }
}
```

#### 3. Add Route

Add routing logic to `src/controller/ApiController.php`:

```php
if ($resource === 'newresource') {
    $controller = new NewResourceController($requestMethod, $this->dbConnection);
    return $controller->processRequest();
}
```

### Code Style Guidelines

- Use **PSR-4 autoloading** (already configured in `composer.json`)
- Follow **PSR-12 coding standards**
- Use **meaningful variable and method names**
- Add **PHPDoc comments** to all methods
- Use **type hints** where possible
- Keep methods **focused and single-purpose**

### Database Migrations

When adding new features:
1. Update database schema
2. Document schema changes
3. Provide migration scripts if needed
4. Update both auth and tenant database schemas as appropriate

## 🧪 Testing

### Manual Testing with cURL

#### Test Authentication

```bash
curl -u "user@example.com:password" \
  http://localhost/CommerceApi/
```

#### Test Product Operations

**List Products**:
```bash
curl -u "user@example.com:password" \
  http://localhost/CommerceApi/products
```

**Create Product**:
```bash
curl -u "user@example.com:password" \
  -X POST \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Product","price":9.99}' \
  http://localhost/CommerceApi/products
```

**Update Product**:
```bash
curl -u "user@example.com:password" \
  -X PUT \
  -H "Content-Type: application/json" \
  -d '{"id":1,"name":"Updated Product"}' \
  http://localhost/CommerceApi/products
```

**Delete Product**:
```bash
curl -u "user@example.com:password" \
  -X DELETE \
  -H "Content-Type: application/json" \
  -d '{"id":1}' \
  http://localhost/CommerceApi/products
```

#### Test Plan Features

```bash
curl -u "user@example.com:password" \
  http://localhost/CommerceApi/plan
```

#### Test Maintenance Mode

**With Debug Key**:
```bash
curl -u "user@example.com:password" \
  "http://localhost/CommerceApi/products?debug-key=your_debug_key"
```

#### Test Reports

```bash
curl -u "user@example.com:password" \
  "http://localhost/CommerceApi/reports?reporttype=Sales&begindate=2025-01-01&enddate=2025-01-31"
```

#### Test Client Authentication

The API includes comprehensive test scripts for client authentication:

```bash
cd src/Scripts

# Test valid authentication (expect 200 OK)
php test_client_auth_valid.php

# Test invalid API key (expect 403 Forbidden)
php test_client_auth_invalid_key.php

# Test invalid signature (expect 403 Forbidden)
php test_client_auth_invalid_signature.php

# Test expired timestamp (expect 403 Forbidden)
php test_client_auth_expired.php

# Test rate limiting (expect 429 after limit)
php test_rate_limiting.php

# Test POST request with body (expect 200/201)
php test_post_request.php

# Test query string inclusion (expect 200 OK)
php test_query_string.php
```

**What Each Test Validates**:
- `test_client_auth_valid.php` - Properly signed requests work correctly
- `test_client_auth_invalid_key.php` - Invalid API keys are rejected
- `test_client_auth_invalid_signature.php` - Tampered requests are rejected
- `test_client_auth_expired.php` - Old timestamps are rejected (replay attack prevention)
- `test_rate_limiting.php` - Rate limits are enforced properly
- `test_post_request.php` - POST/PUT requests with bodies are signed correctly
- `test_query_string.php` - Query parameters are included in signature

### Testing Checklist

- [ ] Authentication with valid credentials
- [ ] Authentication with invalid credentials
- [ ] All CRUD operations for each resource
- [ ] Feature gating for different plan types
- [ ] Maintenance mode activation/deactivation
- [ ] Debug key override in maintenance mode
- [ ] Error responses (400, 401, 403, 404, 429, 500, 503)
- [ ] Report generation with various parameters
- [ ] Client nested resources (sales, sales details)
- [ ] Client authentication with valid API key and signature
- [ ] Client authentication rejection scenarios
- [ ] Rate limiting enforcement
- [ ] Audit logging of requests

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📧 Contact

For questions, support, or feedback:

- **GitHub Issues**: [Report a bug or request a feature](https://github.com/raulpellizzer/CommerceApi/issues)
- **Email**: Contact the repository owner
- **Documentation**: Refer to this README for comprehensive guidance

---

**Built with ❤️ by Raul Pellizzer**

*Last Updated: February 2026*