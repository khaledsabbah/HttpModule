# Circuit Breaker - Implementation Overview

> **Brief:** Automatic failure detection and recovery mechanism for HTTP clients. Prevents cascading failures by "opening the circuit" when a service is unhealthy.

---

## What is Circuit Breaker?

A circuit breaker monitors HTTP requests and automatically stops sending requests to failing services. It has three states:

- **CLOSED** → Normal operation, requests flow through
- **OPEN** → Service failing, requests are blocked (fail fast)
- **HALF_OPEN** → Testing recovery, limited requests allowed

```
CLOSED → (failures exceed threshold) → OPEN
OPEN → (wait interval) → HALF_OPEN
HALF_OPEN → (success) → CLOSED
HALF_OPEN → (failure) → OPEN
```
---

## File Structure

```
src/CircuitBreaker/
├── CircuitBreaker.php              # Core circuit breaker logic
├── CircuitBreakerFactory.php       # Factory to create instances
├── CircuitBreakerInterceptor.php   # HTTP request/response hooks
│
├── Config/
│   ├── CircuitBreakerConfig.php    # Base configuration (abstract)
│   ├── CountStrategyConfig.php     # Config for count-based strategy
│   └── RateStrategyConfig.php      # Config for rate-based strategy
│
├── Contracts/
│   ├── CircuitBreakerStorage.php   # Storage interface
│   └── StrategyInterface.php       # Strategy interface
│
├── Enums/
│   └── CircuitState.php            # CLOSED, OPEN, HALF_OPEN
│
├── Exceptions/
│   └── CircuitOpenException.php    # Thrown when circuit is open
│
├── Storage/
│   ├── CacheStorage.php            # Laravel Cache implementation
│   └── RedisStorage.php            # Redis implementation
│
└── Strategy/
    ├── CountStrategy.php           # Trip after N failures
    └── RateStrategy.php            # Trip when failure rate > X%
```

---

## Key Components

### 1. Strategies (Tripping Logic)

Determines **when** to open the circuit.

#### **CountStrategy**
- Trips after **N consecutive failures** within time window
- Example: Open circuit after 5 failures in 60 seconds
- Use case: Simple failure counting

#### **RateStrategy**
- Trips when **failure rate > X%** (requires minimum requests)
- Example: Open circuit when 50% of last 10 requests fail
- Use case: Percentage-based thresholds

### 2. Storage (State Persistence)

Stores circuit state and metrics.

#### **RedisStorage**
- Uses Redis for distributed state across servers
- Key format: `{prefix}:{service}:{metric}`
- Supports TTL for auto-cleanup

#### **CacheStorage**
- Uses Laravel Cache (file, Redis, Memcached, etc.)
- Configurable cache store
- Same interface as RedisStorage

### 3. Configuration

#### **CountStrategyConfig**
```php
CountStrategyConfig::make()
    ->failureCountThreshold(5)      // Trip after 5 failures
    ->timeWindow(60)                // Within 60 seconds
    ->intervalToHalfOpen(30)        // Wait 30s before retry
    ->successThreshold(3)           // 3 successes to close
    ->storage('redis');             // Use Redis
```

#### **RateStrategyConfig**
```php
RateStrategyConfig::make()
    ->failureRateThreshold(50.0)    // Trip at 50% failure rate
    ->minimumRequests(10)           // Need 10 requests minimum
    ->timeWindow(60)                // Within 60 seconds
    ->intervalToHalfOpen(30)        // Wait 30s before retry
    ->successThreshold(3)           // 3 successes to close
    ->storage('cache');             // Use Laravel Cache
```

---

## How It Works

### Request Flow

```
1. HTTP Request
   ↓
2. CircuitBreakerInterceptor::before()
   ↓
3. Check: CircuitBreaker::isAvailable()
   ├─ CLOSED/HALF_OPEN → Allow request (proceed to step 4)
   └─ OPEN → Throw CircuitOpenException (stop here)
   ↓
4. Execute HTTP request
   ↓
5. Success?
   ├─ YES → CircuitBreakerInterceptor::after() → record success
   └─ NO → CircuitBreakerInterceptor::onException() → record failure
   ↓
6. Strategy evaluates if circuit should trip
   ├─ Threshold exceeded? → Transition to OPEN
   └─ Normal → Stay CLOSED
```

### State Transitions

**CLOSED → OPEN**
- Strategy detects threshold exceeded (failures or rate)
- Circuit blocks all requests
- Sets `opened_at` timestamp

**OPEN → HALF_OPEN**
- After `intervalToHalfOpen` seconds (default: 30s)
- Next request triggers transition
- Allows limited testing

**HALF_OPEN → CLOSED**
- After `successThreshold` consecutive successes (default: 3)
- Circuit fully recovers
- Resets all metrics

**HALF_OPEN → OPEN**
- Any failure during testing
- Circuit immediately reopens

---

## Usage Example

### Basic Setup

```php
// In your HTTP client class
class MudadClient extends Client
{
    protected function circuitBreakerConfig(): ?CircuitBreakerConfig
    {
        return RateStrategyConfig::make()
            ->failureRateThreshold(40.0)    // 40% failure rate
            ->minimumRequests(10)            // Need 10 requests
            ->timeWindow(60)                 // In 60 seconds
            ->intervalToHalfOpen(30)         // Wait 30s to retry
            ->successThreshold(3)            // 3 successes to recover
            ->storage('redis')               // Use Redis
            ->prefix('cb:mudad');            // Custom prefix
    }
}
```

### Making Requests

```php
try {
    $client = new MudadClient();
    $response = $client->do($request);
    // Success - circuit records success
} catch (CircuitOpenException $e) {
    // Circuit is open - service is down
    // Use fallback logic
} catch (Throwable $e) {
    // Other error - circuit records failure
}
```
---

## Configuration Options

### Common Settings (Both Strategies)

| Option | Default | Description |
|--------|---------|-------------|
| `timeWindow` | 60 | Time window in seconds to track metrics |
| `intervalToHalfOpen` | 30 | Seconds to wait before attempting recovery |
| `successThreshold` | 3 | Consecutive successes needed to close circuit |
| `storage` | 'cache' | Storage type: 'redis' or 'cache' |
| `prefix` | 'cb:app' | Cache/Redis key prefix |
| `failureStatusCodes` | [500,502,503,504] | HTTP codes considered failures |
| `ignoredStatusCodes` | [] | HTTP codes to ignore |

### CountStrategy Specific

| Option | Default | Description |
|--------|---------|-------------|
| `failureCountThreshold` | 5 | Number of failures to trip circuit |

### RateStrategy Specific

| Option | Default | Description |
|--------|---------|-------------|
| `failureRateThreshold` | 50.0 | Failure rate percentage (0-100) |
| `minimumRequests` | 10 | Minimum requests before evaluating rate |

### Storage Options

| Option | Description | When to Use |
|--------|-------------|-------------|
| `redisConnection()` | Specify Redis connection name | Multiple Redis servers |
| `cacheStore()` | Specify cache store name | Multiple cache backends |

---