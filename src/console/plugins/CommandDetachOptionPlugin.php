<?php

namespace framework\console\plugins;

use framework\console\ConsoleEvent;
use framework\console\contracts\ConsoleInputInterface;
use framework\console\contracts\ConsoleInputPluginInterface;
use framework\console\contracts\ConsoleKernelInterface;
use framework\console\contracts\ConsoleOutputInterface;
use framework\event_dispatcher\EventDispatcherInterface;
use framework\event_dispatcher\Message;
use framework\event_dispatcher\ObserverInterface;

class CommandDetachOptionPlugin implements ConsoleInputPluginInterface, ObserverInterface
{
    private string $optionName;

    public function __construct(
        private readonly ConsoleInputInterface $input,
        private readonly ConsoleOutputInterface $output,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
        $this->optionName = 'detach';
    }

    public function init(): void
    {
        $this->input->addDefaultOption($this->optionName, 'Плагин перевода выполнения команды в фоновый режим');
        $this->dispatcher->attach(ConsoleEvent::CONSOLE_INPUT_AFTER_PARSE->value, self::class);
    }

    public function observe(Message $message): void
    {
        if ($this->input->hasOption($this->optionName) === false) {
            return;
        }

        $this->output->detach();
    }
}