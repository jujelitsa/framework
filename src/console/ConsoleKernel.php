<?php

namespace jujelitsa\framework\console;

use jujelitsa\framework\console\commands\ListCommand;
use jujelitsa\framework\console\contracts\ConsoleCommandInterface;
use jujelitsa\framework\console\contracts\ConsoleInputInterface;
use jujelitsa\framework\console\contracts\ConsoleKernelInterface;
use jujelitsa\framework\console\contracts\ConsoleOutputInterface;
use jujelitsa\framework\container\DiContainer;
use jujelitsa\framework\contracts\ErrorHandlerInterface;
use jujelitsa\framework\logger\LoggerInterface;

/**
 * Ядро обработки вызова консоли
 */
class ConsoleKernel implements ConsoleKernelInterface
{
    private string $defaultCommand = 'list';

    private array $commandMap = [];

    public function __construct(
        private readonly DIContainer $container,
        private readonly ConsoleInputInterface $input,
        private readonly ConsoleOutputInterface $output,
        private readonly LoggerInterface $logger,
        private readonly ErrorHandlerInterface $errorHandler,
        private readonly string $appName,
        private readonly string $version,
    )
    {
        $this->initDefaultCommands();
    }

    /**
     * Возврат имени приложения
     *
     * @return string
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * Возврат версии приложения
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Возврат карты команд
     *
     * @return string
     */
    public function getCommands(): array
    {
        return $this->commandMap;
    }

    /**
     * Регистрация неймспейсов команд
     *
     * @return string
     */
    public function registerCommandNamespaces(array $commandNameSpaces): void
    {
        foreach ($commandNameSpaces as $commandNameSpace) {
            $this->registerCommandNamespace($commandNameSpace);
        }
    }

    /**
     * Регистрация класса команды
     *
     * @return string
     */
    private function registerCommand(string $className): void
    {

        if (is_subclass_of($className, ConsoleCommandInterface::class) === false) {
            throw new \InvalidArgumentException($className . " не соответствует интерфейсу " . ConsoleCommandInterface::class);
        }

        $commandName = (new CommandDefinition($className::getSignature(), $className::getDescription()))->getCommandName(); // ошибка

        $this->commandMap[$commandName] = $className;
    }

    /**
     * Регистрация неймспейса команды
     *
     * @return string
     */
    private function registerCommandNamespace(string $commandNameSpace): void
    {
        $paths = glob($commandNameSpace . '/*.php');

        foreach (glob($commandNameSpace . '/*', GLOB_ONLYDIR) as $subDir) {
            $this->registerCommandNamespace($subDir);
        }

        foreach ($paths as $path) {

            $fileName = basename($path, '.php');

            if (preg_match('/^[A-Z]/', $fileName) === 0) {
                continue;
            }

            $namespaceMatch = [];

            if (preg_match('/^namespace\s+([^;]+);/m', file_get_contents($path), $namespaceMatch) === false) {
                continue;
            }

            $namespace = trim($namespaceMatch[1]);


            $commandClass = $namespace . '\\' . $fileName;

            if (class_exists($commandClass) === false) {
                continue;
            }

            $this->registerCommand($commandClass);
        }
    }

    /**
     * Регистрация команд по-умолчанию
     *
     * @return string
     */
    private function initDefaultCommands(): void
    {
        $defaultCommands = [
            ListCommand::class,
        ];

        foreach ($defaultCommands as $className) {
            $this->registerCommand($className);
        }
    }

    /**
     * Обработка запроса
     *
     * @return string
     */
    public function handle(): int
    {
        $commandName = $this->input->getFirstArgument() ?? $this->defaultCommand;
        $commandName = $this->commandMap[$commandName]
            ?? throw new \InvalidArgumentException(sprintf("Команда %s не найдена", $commandName));

        try {

            $this->container
                ->build($commandName)
                ->execute($this->input, $this->output);

        } catch (\Throwable $e) {

            $message = $this->errorHandler->handle($e);

            $this->output->stdErr($message);

            $this->logger->error($e);

            return 1;
        }

        return 0;
    }

    public function terminate(int $status): never
    {
        exit($status);
    }
}
