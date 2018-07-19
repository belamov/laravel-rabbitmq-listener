<?php

namespace Belamov\RabbitMQListener\EventHandlers;

use Belamov\RabbitMQListener\Exceptions\EventParsingException;

class EventsFactory
{
    /**
     * @param $eventName
     * @param $payload
     * @return EventHandler
     * @throws EventParsingException
     */
    public static function build($eventName, $payload): EventHandler
    {
        $events = config('rabbitmq_listener.events');
        $ignoredEvents = config('rabbitmq_listener.ignored_events');

        if (!key_exists($eventName, $events)) {
            throw new EventParsingException("Unknown event {$eventName}");
        }

        if (key_exists($eventName, $ignoredEvents)) {
            throw new EventParsingException("Event {$eventName} is ignored");
        }

        return new $events[$eventName]($payload);
    }
}