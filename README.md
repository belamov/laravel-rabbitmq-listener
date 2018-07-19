# Laravel RabbitMQ listener

This package provides you with artisan command that will listen to incoming RabbitMQ events from various queues.

## Basic usage

### 1) Configuration

After installing package publish its config with ```php artisan vendor:publish``` command.

In your config folder customize ```rabbitmq_listener.php``` file to your needs.

Available config parameters are:
   
* events - which events you wish to listen and which classes are responsible for each event 
* ignored_events - which event you wish to ignore
* queues - which queues you wish to listen
* reject_events - determines if you want to reject incoming events (this is useful during debugging)
* acknowledge_events - determines if you want to acknowledge events (this is useful during debugging)

For connection to RabbitMQ add following parameters to your ```.env``` file:

* RABBITMQ_LISTENER_HOST
* RABBITMQ_LISTENER_PORT
* RABBITMQ_LISTENER_USER
* RABBITMQ_LISTENER_PASSWORD
* RABBITMQ_LISTENER_VHOST

### 2)  Implement abstract event parser

In your app you must create class that extends ```Belamov\RabbitMQListener\AbstractEventParser``` class

This abstract class consists of following methods:

```php
abstract public function setEventPayload(string $payload);

abstract public function getEventName();

abstract public function getEventPayload();
```

```setEventPayload($payload)``` is called when your event parser is created. ```$payload``` is string that came from your RabbitMQ event.

```getEventName()``` is used to determine which event handler will be used. If you have only one type of event then just make it return specific value which you will configure in ```events``` parameter in ```rabbitmq_listener.php``` file 

```getEventPayload()``` is used when you instantiating your event handler. Returned value injects in handler's constructor.

After creating parser class you should register it in your ```appServiceProvider.php``` file.

Simply add following code to  ```register()``` method:

```php
$this->app->bind(
    'Belamov\RabbitMQListener\AbstractEventParser',
    'Your\Namespace\<Your class name>'
);
```

### 3) Add your event handlers

For working with events create classes that extend Belamov\RabbitMQListener\EventHandlers\EventHandler class

This class is requires payload that will be returned from your ```getEventPayload()``` method.

```process()``` method may return some info that will be returned in your console when you process events.

You are also able to throw ```Belamov\RabbitMQListener\Exceptions\EventParsingException``` in your process method. If it is thrown than event will be rejected without rescheduling (if you set ```reject_events``` to true in config file)

### 4) Run command

run ```php artisan rabbitmq:listen``` command. You can also put this process under supervisor for rerunning it constantly. 