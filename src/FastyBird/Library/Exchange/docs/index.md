# Quick start

The purpose of this library is to provide unified interface for data exchange bus.

Create consumers and publishers proxies, collect registered application consumers and publishers and rule them.

***

## Installation

The best way to install **fastybird/exchange-library** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/exchange-library
```

After that, you have to register extension in *services.neon*.

```neon
extensions:
    fbExchangeLibrary: FastyBird\Library\Exchange\DI\ExchangeExtension
```

## Creating custom publisher

If some service of your extension have to publish messages to data exchange bus for other extensions, you could just
implement `FastyBird\Library\Exchange\Publisher\Publisher` interface and register your publisher as service

```php
namespace Your\CoolApp\Publishers;

use FastyBird\Library\Exchange\Publisher\Publisher;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette\Utils;

class ModuleDataPublisher implements Publisher
{

    public function publish(
        MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
        MetadataTypes\RoutingKey $routingKey,
        MetadataDocuments\Document|null $entity,
    ) : void {
        // Service logic here, e.g. publish message to RabbitMQ or Redis etc. 
    }

}
```

You could create as many publishers as you need. Publisher proxy then will collect all of them.

## Publishing message

In your code you could just import one publisher - proxy publisher.

```php
namespace Your\CoolApp\Actions;

use FastyBird\Library\Exchange\Publisher\Container;

class SomeHandler
{

    /** @var Container */
    private Container $publisher;

    public function __construct(
        Container $publisher
    ) {
        $this->publisher = $publisher;
    }

    public function updateSomething()
    {
        // Your interesting logic here...

        $this->publisher->publish(
            $origin,
            $routingKey,
            $entity,
        );
    }
}
```

And that is it, global publisher will call all your publishers and publish message to all your systems.

## Creating custom consumer

One part is done, message is published. Now have to be consumed.

If some service of your extension have is waiting for messages from data exchange bus from other extensions, you could just
implement `FastyBird\Library\Exchange\Consumer\Consumer` interface and register your consumer as service

```php
namespace Your\CoolApp\Publishers;

use FastyBird\Library\Exchange\Consumers\Consumer;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;

class DataConsumer implements Consumer
{

    public function consume(
        MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
        MetadataTypes\RoutingKey $routingKey,
        MetadataDocuments\Document|null $entity,
    ) : void {
        // Do your data processing logic here 
    }

}
```

You could create as many consumers as you need. Consumer proxy then will collect all of them.

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and
repository [https://github.com/FastyBird/exchange-library](https://github.com/FastyBird/exchange-library).
