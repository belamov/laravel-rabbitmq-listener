<?php

namespace Belamov\RabbitMQListener\Commands;

use Belamov\RabbitMQListener\AbstractEventParser;
use Belamov\RabbitMQListener\EventHandlers\EventsFactory;
use Belamov\RabbitMQListener\Exceptions\EventParsingException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ListenToRabbitMQCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listens to events from RabbitMQ';

    protected $channel;
    protected $connection;
    protected $parser;

    public function connect()
    {
        $this->connection = new AMQPStreamConnection(
            config('rabbitmq_listener.host'),
            config('rabbitmq_listener.port'),
            config('rabbitmq_listener.user'),
            config('rabbitmq_listener.password'),
            config('rabbitmq_listener.vhost')
        );
        $this->channel = $this->connection->channel();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->connect();

        foreach (config('rabbitmq_listener.queues') as $queue) {
            $this->channel->queue_declare($queue, false, true, false, false);
            $this->info(" [*] Waiting for messages. To exit press CTRL+C\n");
            $callback = function ($msg) {
                try {
                    $message = $msg->body;

                    $parser = resolve(AbstractEventParser::class);
                    $parser->setEventPayload($message);

                    $handler = EventsFactory::build($parser->getEventName(), $parser->getEventPayload());

                    $this->info($handler->process());

                    if (config('rabbitmq_listener.acknowledge_events')) {
                        $this->acknowledgeEvent($msg);
                    }
                } catch (EventParsingException $e) {
                    if (config('rabbitmq_listener.reject_events')) {
                        Log::error("RABBITMQ LISTENER: " . $e->getMessage());
                        $this->rejectWithoutReschedule($msg);
                    }
                    $this->error($e->getMessage());
                } catch (\Exception $e) {
                    if (config('rabbitmq_listener.reject_events')) {
                        Log::error("RABBITMQ LISTENER: " . $e->getMessage());
                        $this->rejectWithReschedule($msg);
                    }
                    $this->error($e->getMessage());
                }
            };
            $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

        }
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->channel->close();
        $this->connection->close();
    }

    protected function acknowledgeEvent($msg): void
    {
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    protected function rejectWithoutReschedule($msg): void
    {
        $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
    }

    protected function rejectWithReschedule($msg): void
    {
        $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
    }
}
