# Remote Objects Architecture

This document describes the RemoteObject architecture in Playwright PHP, which provides parent→child ownership management and cascade disposal for remote Playwright objects.

## Overview

The RemoteObject system manages the lifecycle of remote Playwright objects (Browser, BrowserContext, Page) and ensures proper cleanup when objects are disposed. It implements a parent-child relationship model where disposing a parent automatically disposes all its children.

## Core Components

### RemoteObject (Base Class)

The `RemoteObject` abstract class provides the foundation for all remote Playwright objects:

```php
use PlaywrightPHP\Internal\RemoteObject;
use PlaywrightPHP\Transport\TransportInterface;

abstract class RemoteObject
{
    public function __construct(
        protected readonly TransportInterface $transport,
        protected readonly string $remoteId,
        protected readonly string $remoteType,
    ) {}
    
    public function dispose(): void { /* ... */ }
    public function addChild(RemoteObject $child): void { /* ... */ }
    public function removeChild(RemoteObject $child): void { /* ... */ }
    protected function onDispose(): void { /* Override in subclasses */ }
}
```

Key features:
- **Disposal cascading**: When `dispose()` is called, all children are disposed first
- **Idempotent disposal**: Multiple calls to `dispose()` are safe
- **Parent-child tracking**: Automatic management of object relationships
- **Lifecycle hooks**: `onDispose()` method for cleanup logic

### OwnershipRegistry

The `OwnershipRegistry` provides static methods for managing object relationships:

```php
use PlaywrightPHP\Internal\OwnershipRegistry;

// Register objects
OwnershipRegistry::register($remoteObject);

// Link parent-child relationships
OwnershipRegistry::linkParentChild($browser, $context);

// Dispose with cascading
OwnershipRegistry::disposeCascade('browser-id');

// Reset (useful for testing)
OwnershipRegistry::reset();
```

## Object Hierarchy

The ownership model follows this hierarchy:

```
Browser
├── BrowserContext (default)
│   ├── Page
│   ├── Page
│   └── ...
├── BrowserContext (custom)
│   ├── Page
│   └── ...
└── ...
```

### Browser → BrowserContext → Page

When objects are created, they are automatically linked:

```php
$browser = new Browser($transport, 'browser-123', 'context-123', '1.0');
// Browser automatically creates and links default context

$context = $browser->newContext();
// Context is automatically linked as browser child

$page = $context->newPage();
// Page is automatically linked as context child
```

## Disposal Process

When an object is disposed, the following sequence occurs:

1. **Child disposal**: All children are disposed recursively
2. **Parent cleanup**: Object removes itself from parent
3. **Mark disposed**: Object is marked as disposed
4. **Custom cleanup**: `onDispose()` method is called for transport cleanup

### Example: Browser Disposal

```php
$browser->close(); // Calls dispose() internally

// This will:
// 1. Dispose all BrowserContext children
//    - Each context disposes its Page children first
//    - Each page sends 'page.close' to transport
//    - Context sends 'context.close' to transport
// 2. Browser sends 'close' action to transport
// 3. All objects marked as disposed
```

## Channel Support

The system also supports browser channel configuration through the builder pattern:

### Configuration

Channels can be configured through environment variables or fluent API:

```bash
# Environment variable
export PW_CHANNEL=msedge

# Or via config builder
$config = PlaywrightConfigBuilder::create()
    ->withChannel('msedge')
    ->build();
```

### Browser Launch

The channel is automatically passed to the browser launch process:

```php
$client = new PlaywrightClient($transport, $logger, $config);
$browser = $client->chromium()
    ->withChannel('msedge')  // Or automatically from config
    ->launch();
```

This sends the following transport message:

```json
{
    "action": "launch",
    "browser": "chromium",
    "options": {
        "channel": "msedge",
        "headless": true
    }
}
```

## Implementation Details

### Composition Pattern

Objects use composition rather than inheritance to integrate with RemoteObject:

```php
class Browser implements BrowserInterface
{
    private RemoteObject $remoteObject;
    
    public function __construct(/* ... */) {
        $this->remoteObject = new BrowserRemoteObject($transport, $browserId, 'browser');
        OwnershipRegistry::register($this->remoteObject);
    }
    
    public function close(): void {
        $this->remoteObject->dispose();
    }
    
    public function getRemoteObject(): RemoteObject {
        return $this->remoteObject;
    }
}
```

### Custom RemoteObject Implementations

Each object type has its own RemoteObject implementation:

```php
class BrowserRemoteObject extends RemoteObject
{
    protected function onDispose(): void
    {
        $this->transport->send([
            'action' => 'close',
            'browserId' => $this->remoteId,
        ]);
    }
}
```

## Benefits

1. **Automatic cleanup**: No need to manually close all objects
2. **Memory safety**: Prevents memory leaks from unclosed objects  
3. **Idempotent operations**: Safe to call close() multiple times
4. **Clear hierarchy**: Well-defined parent-child relationships
5. **Extensible**: Easy to add new object types

## Testing

The architecture includes comprehensive tests:

- Unit tests for `RemoteObject` and `OwnershipRegistry`
- Integration tests for browser→context→page disposal cascading
- Channel passthrough tests for configuration

Example test:

```php
#[Test]
public function itCascadesDisposalFromBrowserToChildren(): void
{
    $browser = new Browser($transport, 'browser-123', 'context-123', '1.0');
    $context = $browser->newContext();
    $page = $context->newPage();

    $browser->close();

    $this->assertTrue($browser->isDisposed());
    $this->assertTrue($context->isDisposed());
    $this->assertTrue($page->isDisposed());
}
```

This architecture ensures robust resource management and provides a solid foundation for future Playwright PHP development.