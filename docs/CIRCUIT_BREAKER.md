# Circuit Breaker

## What Is It?

Protects your application from cascading failures when external services become unreliable. Automatically stops requests to failing services and gives them time to recover.

**What "Trip" Means:**
- **Trip** = Circuit transitions from CLOSED (normal) to OPEN (blocking requests)
- When circuit "trips", it opens and blocks all requests to protect your system
- After a cooldown period, it tests if the service recovered

---

## Three Circuit States

```
CLOSED 🟢 (Normal - all requests allowed)
    ↓
    Too many failures detected
    ↓
OPEN 🔴 (Service down - block ALL requests)
    ↓
    Wait for cooldown period (e.g., 30s)
    ↓
HALF_OPEN ⚠️ (Testing - allow FEW requests to test recovery)
    ↓
    If 3 successes → CLOSED 🟢 (recovered!)
    If 1 failure   → OPEN 🔴 (still broken, wait longer)
```

### Why HALF_OPEN is Needed

Without HALF_OPEN, you'd have two bad options:
- ❌ **Stay OPEN forever** → Never recover, block healthy services
- ❌ **Auto-CLOSE immediately** → Flood failing service with thousands of requests

**HALF_OPEN = Controlled Testing**
- After cooldown (e.g., 30s), test with just a FEW requests (e.g., 3)
- If those succeed → Service is healthy → Fully reopen
- If any fail → Service still broken → Wait longer

---

## How to Use

### Option 1: Custom Configuration Per Service

```php
use Idaratech\Integrations\Client;
use Idaratech\Integrations\CircuitBreaker\Config\RateStrategyConfig;

class PaymentClient extends Client
{
    protected function circuitBreakerConfig(): ?CircuitBreakerConfig
    {
        return RateStrategyConfig::make()
            ->failureRateThreshold(40.0)   // Trip at 40% failure rate
            ->minimumRequests(10)           // Need 10 requests minimum
            ->timeWindow(60)                // Track failures in last 60s
            ->intervalToHalfOpen(30)        // Wait 30s before testing recovery
            ->successThreshold(3)           // Need 3 successes to close
            ->storage('redis');
    }
}
```

### Option 2: Count Strategy (Absolute Failures)

```php
use Idaratech\Integrations\CircuitBreaker\Config\CountStrategyConfig;

class EmailClient extends Client
{
    protected function circuitBreakerConfig(): ?CircuitBreakerConfig
    {
        return CountStrategyConfig::make()
            ->failureCountThreshold(5)      // Trip after 5 failures
            ->intervalToHalfOpen(120)       // Wait 2 minutes
            ->storage('redis');
    }
}
```

---

## Strategies Explained

### Rate Strategy (Percentage-Based)

Trips when **failure percentage** exceeds threshold.

**Formula:**
```
failureRate = (failures / totalRequests) × 100

if totalRequests >= minimumRequests AND failureRate >= threshold:
    TRIP → OPEN
```

**Example:**
```php
RateStrategyConfig::make()
    ->failureRateThreshold(50.0)    // 50% threshold
    ->minimumRequests(10);           // Need at least 10 requests
```

**Scenarios:**
```
Scenario 1: Not enough data
Total: 8 requests, 5 failures (62.5% failure rate)
Result: DON'T TRIP (8 < 10 minimum requests)

Scenario 2: Below threshold
Total: 20 requests, 8 failures (40% failure rate)
Result: DON'T TRIP (40% < 50%)

Scenario 3: Exceeds threshold - TRIP!
Total: 20 requests, 11 failures (55% failure rate)
Result: TRIP! (55% >= 50% AND 20 >= 10)
```

**Best for:** High-traffic services, external APIs

---

### Count Strategy (Absolute Count)

Trips when **absolute failure count** exceeds threshold.

**Formula:**
```
if failures >= threshold:
    TRIP → OPEN
```

**Example:**
```php
CountStrategyConfig::make()
    ->failureCountThreshold(5);     // Trip after 5 failures
```

**Scenarios:**
```
Scenario 1: Below threshold
Failures: 3, Successes: 100
Result: DON'T TRIP (3 < 5)

Scenario 2: Reaches threshold - TRIP!
Failures: 5, Successes: 2
Result: TRIP! (5 >= 5)
```

**Best for:** Critical services (payments), low-traffic services, strict failure policies

---

### Strategy Comparison

| Aspect | Rate Strategy | Count Strategy |
|--------|--------------|----------------|
| **Trips based on** | Percentage (failures/total) | Absolute number |
| **Requires minimum requests** | Yes | No |
| **Formula** | `(failures/total)×100 >= 50%` | `failures >= 5` |
| **Best for** | High traffic, variable load | Critical systems, low traffic |
| **Example** | 11 failures out of 20 = 55% | 5 failures (regardless of total) |

---

## Configuration Parameters Explained

### 1. `timeWindow` - Memory Span (ALWAYS Active)

**What it does:** Defines how long to "remember" failures and successes

**Always running:** Tracks in ALL states (CLOSED, OPEN, HALF_OPEN)

**Example:**
```php
->timeWindow(60)  // Track failures in last 60 seconds
```

**How it works:**
```
Current time: 10:01:00

Failures in storage:
09:59:50 - Failure ❌ EXPIRED (outside 60s window)
10:00:05 - Failure ✓ COUNTED (within 60s)
10:00:30 - Failure ✓ COUNTED (within 60s)
10:00:50 - Failure ✓ COUNTED (within 60s)

Only count: 3 failures (last 60 seconds)
Old failures automatically expire!
```

**Purpose:**
- Prevents old failures from affecting current decisions
- Keeps circuit breaker responsive to current service health
- Creates a "sliding window" that moves with time

---

### 2. `intervalToHalfOpen` - Recovery Cooldown (Only When OPEN)

**What it does:** How long to wait after circuit opens before testing recovery

**Only active:** When circuit is OPEN

**Example:**
```php
->intervalToHalfOpen(30)  // Wait 30 seconds before testing
```

**How it works:**
```
10:00:00 - Circuit OPENS
           Start timer from HERE

10:00:10 - Request arrives
           10s < 30s → BLOCKED ⛔

10:00:30 - Request arrives
           30s >= 30s → Transition to HALF_OPEN ✓
           Request ALLOWED (testing recovery)
```

**Purpose:**
- Gives failing service time to recover
- Prevents immediate retry attempts

---

### 3. `successThreshold` - Recovery Test (Only in HALF_OPEN)

**What it does:** How many CONSECUTIVE successes needed to close circuit

**Only active:** When circuit is HALF_OPEN

**NOT tracked in timeWindow!** Separate counter, resets on any failure.

**Example:**
```php
->successThreshold(3)  // Need 3 consecutive successes
```

**How it works:**
```
State: HALF_OPEN

Request 1: 200 ✓ → successCount = 1
Request 2: 200 ✓ → successCount = 2
Request 3: 200 ✓ → successCount = 3
→ Circuit CLOSES! 🟢

Alternative (failure during test):
Request 1: 200 ✓ → successCount = 1
Request 2: 500 ✗ → FAILURE!
→ Circuit goes back to OPEN 🔴
→ Reset counter to 0
→ Wait another 30s before retrying
```

**Purpose:**
- Ensures service is truly recovered (not just one lucky success)
- Prevents flapping (OPEN → CLOSED → OPEN)

---

### Visual Summary of Parameters

```
timeWindow(60) - ALWAYS SLIDING
├─────────────────────────────────────────────────────┤
09:59:50    10:00:00    10:00:30    10:01:00
   ❌          ✓           ✓         NOW
expired    counted     counted
└──────────── Only count last 60s ────────────┘


intervalToHalfOpen(30) - ONLY WHEN OPEN
Circuit OPENS ├─────────────┤ Test recovery
         10:00:00      10:00:30
                          ↓
                    HALF_OPEN


successThreshold(3) - ONLY IN HALF_OPEN
HALF_OPEN: ✓ ✓ ✓ → CLOSED
HALF_OPEN: ✓ ✗   → Back to OPEN
           └─ Reset counter
```

---

## Complete Lifecycle Example

**Configuration:**
```php
RateStrategyConfig::make()
    ->failureRateThreshold(50.0)   // 50% failures
    ->minimumRequests(10)
    ->timeWindow(60)                // Track last 60s
    ->intervalToHalfOpen(30)        // Wait 30s before testing
    ->successThreshold(3);          // Need 3 successes
```

**Timeline:**

```
State: CLOSED 🟢
════════════════════════════════════════════════════════
10:00:00-10:00:40 - 9 requests (4 success, 5 failures)
                    9 < 10 minimum → Don't trip yet

10:00:45 - Request 10: 500 ✗
           Total: 10 requests (4 success, 6 failures)
           Failure rate: (6/10) × 100 = 60%
           60% >= 50% threshold → TRIP! 🔴

State: OPEN 🔴 (openedAt = 10:00:45)
════════════════════════════════════════════════════════
10:00:50 - Request arrives
           Time since opened: 5s < 30s
           → BLOCKED ⛔ (throw CircuitOpenException)

10:01:00 - Request arrives
           15s < 30s → BLOCKED ⛔

10:01:15 - Request arrives (30s passed!)
           30s >= 30s → Transition to HALF_OPEN ⚠️
           → Request ALLOWED (testing)

State: HALF_OPEN ⚠️ (testing recovery)
════════════════════════════════════════════════════════
10:01:15 - Request 1: 200 ✓ (successCount = 1/3)
10:01:20 - Request 2: 200 ✓ (successCount = 2/3)
10:01:25 - Request 3: 200 ✓ (successCount = 3/3)
           3 >= 3 → Circuit CLOSES! 🟢

State: CLOSED 🟢 (service recovered!)
════════════════════════════════════════════════════════
10:01:30 - Back to normal operation
           Start tracking new failures in timeWindow
```

---

## Storage

Circuit state is stored per service using Redis or Cache.

**Key Structure:**
```
circuit_breaker:{service}:state          → "closed", "open", "half_open"
circuit_breaker:{service}:failures       → 3
circuit_breaker:{service}:successes      → 15
circuit_breaker:{service}:opened_at      → 1703520000
circuit_breaker:{service}:half_open_successes → 2
```

**Multiple Services:**
```
circuit_breaker:api.stripe.com:state     → "open"       (down)
circuit_breaker:api.twilio.com:state     → "closed"     (working)
circuit_breaker:payment.internal:state   → "half_open"  (testing)
```

---

## FAQ - Common Questions

### Q1: What does "trip" mean?

**Trip** means the circuit breaker transitions from CLOSED to OPEN state.
- Circuit "trips" when too many failures detected
- Once tripped, circuit is OPEN and blocks all requests
- Protects your system from cascading failures

---

### Q2: Why do we need HALF_OPEN state? Can't we just go CLOSED → OPEN → CLOSED?

**Without HALF_OPEN you'd have problems:**

❌ **Option A:** Circuit stays OPEN forever
- Service recovers but circuit never reopens
- You're blocking requests to a healthy service

❌ **Option B:** Circuit auto-closes after timeout
- Thousands of requests flood the still-broken service
- Circuit trips again immediately
- Creates flapping: OPEN → CLOSED → OPEN → CLOSED...

✅ **HALF_OPEN solves this:**
- Test with just a FEW requests (e.g., 3)
- If they succeed → Service is healthy → Fully reopen
- If any fail → Service still broken → Wait longer
- Prevents flooding, enables controlled recovery

---

### Q3: What's the difference between `timeWindow` and `intervalToHalfOpen`?

**Completely different purposes:**

**`timeWindow(60)`** = Memory span (ALWAYS active)
- How long to track failures/successes
- Creates a sliding 60-second window
- Old failures automatically expire
- Used in ALL states (CLOSED, OPEN, HALF_OPEN)
- Example: "Only count failures from last 60 seconds"

**`intervalToHalfOpen(30)`** = Recovery cooldown (only when OPEN)
- How long to wait after circuit opens before testing
- Only used when circuit is OPEN
- Measured from when circuit opened (openedAt timestamp)
- Example: "Wait 30 seconds before trying recovery"

**Think of it:**
- `timeWindow` = How far back you look when counting
- `intervalToHalfOpen` = How long you wait before testing recovery

---

### Q4: Is `successThreshold(3)` tracked within the `timeWindow(60)`?

**NO!** There are TWO types of success tracking:

**1. Regular successes (CLOSED state)** - YES, tracked in timeWindow
```
Used for calculating failure rate in Rate Strategy
Example: 10 requests = 6 failures + 4 successes (within 60s)
These successes ARE in timeWindow
```

**2. Half-open successes (HALF_OPEN state)** - NO, NOT in timeWindow
```
Separate counter: halfOpenSuccessCount
Used ONLY to decide when to close circuit
NOT subject to timeWindow expiration
Resets to 0 on any failure
These successes are NOT in timeWindow
```

**Example:**
```
State: HALF_OPEN
10:01:00 - Success ✓ (counter = 1)
10:01:05 - Success ✓ (counter = 2)
10:01:10 - Success ✓ (counter = 3) → CLOSE!

Only took 10 seconds (not 60 seconds)
These are consecutive, immediate successes
No timeWindow involved here!
```

---

### Q5: When does the `intervalToHalfOpen` timer start?

**It starts the moment the circuit trips to OPEN.**

When circuit opens, we store `openedAt` timestamp. The timer counts from there.

**Example:**
```php
->intervalToHalfOpen(30)  // 30 seconds
```

```
10:00:00 - Circuit trips to OPEN
           openedAt = 10:00:00 ← Timer starts HERE

10:00:10 - Request arrives
           (10:00:10 - 10:00:00) = 10s < 30s
           → Still in cooldown, BLOCKED

10:00:30 - Request arrives
           (10:00:30 - 10:00:00) = 30s >= 30s
           → Cooldown finished, transition to HALF_OPEN
```

---

### Q6: Can I use different strategies for different services?

**Yes!** Each client can have its own configuration:

```php
class PaymentClient extends Client
{
    protected function circuitBreakerConfig(): ?CircuitBreakerConfig
    {
        // Strict - trip after just 3 failures
        return CountStrategyConfig::make()
            ->failureCountThreshold(3)
            ->intervalToHalfOpen(120);  // 2 min cooldown
    }
}

class EmailClient extends Client
{
    protected function circuitBreakerConfig(): ?CircuitBreakerConfig
    {
        // Flexible - trip at 50% failure rate
        return RateStrategyConfig::make()
            ->failureRateThreshold(50.0)
            ->minimumRequests(10);
    }
}
```

---

### Q7: How do failures expire from the timeWindow?

**Automatically, based on TTL (Time To Live).**

When a failure is recorded, it's stored with expiration = `timeWindow` seconds.

**Example:**
```php
->timeWindow(60)  // 60 seconds
```

```
10:00:00 - Failure recorded
           Stored in Redis/Cache with TTL = 60s
           Will expire at 10:01:00

10:00:30 - Check failure count
           Still counted (30s < 60s TTL)

10:01:00 - Failure expires automatically
           No longer counted

10:01:30 - Check failure count
           This failure is gone (expired)
```

Redis/Cache automatically removes expired keys. You don't need to manually clean up.

---

### Q8: What happens if service fails during HALF_OPEN testing?

**Circuit immediately trips back to OPEN.**

```
State: HALF_OPEN
Request 1: 200 ✓ (successCount = 1)
Request 2: 200 ✓ (successCount = 2)
Request 3: 500 ✗ FAILURE!

Action:
→ Circuit goes back to OPEN 🔴
→ Reset successCount to 0
→ Set NEW openedAt timestamp
→ Must wait another intervalToHalfOpen (e.g., 30s)
→ Then try recovery again
```

**This prevents:**
- Premature recovery (service not fully healthy)
- Flapping between states
- Overwhelming fragile services

---

### Q9: How do I monitor circuit breaker state?

**Check storage directly or add logging:**

```php
// Check state in Redis/Cache
$state = Cache::get('circuit_breaker:api.stripe.com:state');
// Returns: "closed", "open", or "half_open"

$failures = Cache::get('circuit_breaker:api.stripe.com:failures');
```

Or add logging in your client:

```php
try {
    $response = $client->do($request);
} catch (CircuitOpenException $e) {
    \Log::warning('Circuit open for service', [
        'service' => $e->getService(),
        'opened_at' => Cache::get("circuit_breaker:{$e->getService()}:opened_at"),
    ]);

    return ['error' => 'Service temporarily unavailable'];
}
```

---

### Q10: What status codes are considered failures?

**By default: 5xx errors (500, 502, 503, 504)**

You can customize:

```php
RateStrategyConfig::make()
    ->failureStatusCodes([500, 502, 503, 504, 429]);  // Add 429 Too Many Requests
```

**Success = 2xx and 3xx**
**Failure = 4xx and 5xx (or custom list)**

Note: Network errors and exceptions are always considered failures.

---

## Best Practices

1. **Use Rate Strategy for high-traffic services** (external APIs)
2. **Use Count Strategy for critical low-traffic services** (payments)
3. **Set appropriate timeWindow** - Too short = premature trips, too long = slow recovery
4. **Don't set intervalToHalfOpen too low** - Give service time to truly recover
5. **Use Redis for production** - Better performance than cache
6. **Monitor circuit state** - Log when circuits open/close
7. **Handle CircuitOpenException gracefully** - Return user-friendly errors
8. **Test your thresholds** - Start conservative, adjust based on metrics

---
