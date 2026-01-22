<?php

namespace jujelitsa\framework\console\plugins;

use jujelitsa\framework\console\ConsoleEvent;
use jujelitsa\framework\console\contracts\ConsoleInputInterface;
use jujelitsa\framework\console\contracts\ConsoleKernelInterface;
use jujelitsa\framework\console\contracts\ConsoleOutputInterface;
use jujelitsa\framework\event_dispatcher\EventDispatcherInterface;
use jujelitsa\framework\event_dispatcher\ObserverInterface;
use jujelitsa\framework\console\contracts\ConsoleInputPluginInterface;
use jujelitsa\framework\event_dispatcher\Message;

/**
 * Плагин вывода информации о команде
 */
class CommandHelpOptionPlugin implements ConsoleInputPluginInterface, ObserverInterface
{
    private string $optionName;

    public function __construct(
        private readonly ConsoleInputInterface $input,
        private readonly ConsoleOutputInterface $output,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ConsoleKernelInterface $kernel,
    )
    {
        $this->optionName = 'help';
    }

    public function init(): void
    {
        $this->input->addDefaultOption($this->optionName, 'Вывод информации о команде');
        $this->dispatcher->attach(ConsoleEvent::CONSOLE_INPUT_AFTER_PARSE->value, self::class);
    }

    public function observe(Message $message): void
    {
        if ($this->input->hasOption($this->optionName) === false) {
            return;
        }

        $definition = $this->input->getDefinition();

        $this->output->writeLn();
        $this->output->success('Вызов:');
        $this->output->writeLn();

        $callMassage = ' ' . $definition->getCommandName() . ' ';

        foreach ($definition->getArguments() as $argument) {
            $callMassage .= '[' . $argument . ']' . ' ';
        }

        $this->output->stdout($callMassage);

        $this->output->writeLn(2);
        $this->output->info('Назначение:');
        $this->output->writeLn();

        $this->output->stdout('  ' . $definition->getCommandDescription());
        $this->output->writeLn();

        $this->output->writeLn();
        $this->output->info('Аргументы:');
        $this->output->writeLn();

        foreach ($definition->getArguments() as $argument) {

            $argumentDefinition = $definition->getArgumentDefinition($argument);

            $this->output->success(' ' . $argument . ' ');
            $this->output->stdout($argumentDefinition['description'] . ', ');
            $this->output->stdout($argumentDefinition['required'] === true
                ? 'обязательный параметр'
                : 'не обязательный параметр'
            );

            if ($argumentDefinition['default'] !== null) {
                $this->output->stdout(", значение по умолчанию: {$argumentDefinition['default']}");
            }

            $this->output->writeLn();
        }

        $this->output->writeLn();
        $this->output->info('Опции:');
        $this->output->writeLn();

        foreach ($definition->getOptions() as $option) {
            $optionDefinition = $definition->getOptionDefinition($option);

            $this->output->success(' ' . $option);
            $this->output->stdout(' ' . $optionDefinition['description']);
            $this->output->writeLn();
        }

        $this->output->writeLn();

        $this->kernel->terminate(1);
    }
}
