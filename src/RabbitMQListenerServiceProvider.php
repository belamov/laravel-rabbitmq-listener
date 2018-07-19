<?php

namespace Belamov\RabbitMQListener;

use Illuminate\Support\ServiceProvider;

class RabbitMQListenerServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfigs();
        $this->registerConsoleCommands();
    }

    protected function registerConfigs()
    {
        $this->publishes([
            dirname(__DIR__) . '/publishable/config/rabbitmq_listener.php' => config_path('rabbitmq_listener.php')
        ]);

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/publishable/config/rabbitmq_listener.php', 'rabbitmq_listener'
        );
    }

    protected function registerConsoleCommands()
    {
        $this->commands(Commands\ListenToRabbitMQCommand::class);
    }
}