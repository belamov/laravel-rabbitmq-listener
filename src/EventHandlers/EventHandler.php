<?php

namespace Belamov\RabbitMQListener\EventHandlers;


abstract class EventHandler
{
    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public abstract function process();
}