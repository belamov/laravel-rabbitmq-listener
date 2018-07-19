<?php

namespace Belamov\RabbitMQListener;

abstract class AbstractEventParser
{
    protected $payload;

    abstract public function setEventPayload(string $payload);

    abstract public function getEventName();

    abstract public function getEventPayload();
}