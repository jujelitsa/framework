<?php

namespace jujelitsa\framework\console;

use jujelitsa\framework\console\contracts\ConsoleCommandInterface;
use jujelitsa\framework\console\contracts\ConsoleInputInterface;
use jujelitsa\framework\console\contracts\ConsoleInputPluginInterface;
use jujelitsa\framework\container\DiContainer;
use jujelitsa\framework\event_dispatcher\EventDispatcherInterface;
use jujelitsa\framework\event_dispatcher\Message;

class ConsoleInput implements ConsoleInputInterface
{
    /**
     * @var array аргументы введенные в консоль
     */
    private array $tokens = [];

    /**
     * @var array аргументы, переданные как аргументы вызова в консоль
     */
    private array $arguments = [];

    /**
     * @var array опции, доступные для каждой команды по умолчанию
     */
    private array $defaultOptions = [];

    /**
     * @var array опции переданные как аргументы вызова в консоль
     */
    private array $options = [];

    private array $optionValues = [];

    /**
     * @var array объект описания консольного вызова
     */
    private CommandDefinition $definition;

    public function __construct(
        private readonly DIContainer $container,
        private readonly EventDispatcherInterface $dispatcher
    ) {
        $argv ??= $_SERVER['argv'] ?? [];

        array_shift($argv);

        $this->tokens = $argv;
    }

    /**
     * Возврат объекта описания вызванной команды
     *
     * @return CommandDefinition
     */
    public function getDefinition(): CommandDefinition
    {
        return $this->definition;
    }

    /**
     * Регистрация плагинов
     *
     * @param  array $plugins неймспейсы плагинов
     * @return void
     */
    public function addPlugins(array $plugins): void
    {
        foreach ($plugins as $plugin) {
            $reflect = new \ReflectionClass($plugin);

            if ($reflect->implementsInterface(ConsoleInputPluginInterface::class) === false) {
                throw new \RuntimeException('Класс команды не соответствует интерфейсу' . ConsoleInputPluginInterface::class);
            }

            $this->container->build($plugin)->init();
        }
    }

    /**
     * Получение названия вызываемой команды
     *
     * @return string|null
     */
    public function getFirstArgument(): string|null
    {
        return $this->tokens[0] ?? null;
    }

    /**
     * Преобразование введенных аргументов в консоль в аргументы и опции вызова команды
     *
     * @return void
     */
    private function parse(): void
    {
        $optionToken = null;

        foreach ($this->tokens as $key => $token) {
            if ($key === 0) {
                continue;
            }

            if (str_starts_with($token, '--') === true) {
                $this->parseOption($token);
                $optionToken = substr($token, 2);
                continue;
            }

            if ($optionToken !== null) {
                $this->optionValues[$optionToken] = $token;
                $optionToken = null;
                continue;
            }

            $this->parseArgument($token);
        }
    }

    public function getOptionValue(string $name): string|null
    {
        return $this->optionValues[$name] ?? null;
    }

    /**
     * Валидация аргументов, переданных для вызова команды
     *
     * @return void
     */
    private function validate(): void
    {
        foreach ($this->definition->getArguments() as $arg) {
            if (isset($this->arguments[$arg]) === false && $this->definition->isRequired($arg) === true) {
                throw new \InvalidArgumentException('Не указан обязательный параметр ' . $arg);
            }
        }
    }

    /**
     * Установка значений по умолчанию для аргументов вызова команды,
     * имеющих значения по умолчанию
     *
     * @return void
     */
    private function setDefaults(): void
    {
        foreach ($this->definition->getArguments() as $arg) {
            if (isset($this->arguments[$arg]) === false && $this->definition->isRequired($arg) === false) {
                $this->arguments[$arg] = $this->definition->getDefaultValue($arg);
            }
        }
    }

    /**
     * Регистрация объекта описания консольного вызова
     *
     * @param ConsoleCommandInterface $command инстанс вызываемой команды
     * @return void
     */
    public function bindDefinitions(ConsoleCommandInterface $command): void
    {
        $this->arguments = [];
        $this->options = array_fill_keys(array_keys($this->defaultOptions), false);

        $this->dispatcher->trigger(ConsoleEvent::CONSOLE_INPUT_BEFORE_PARSE->value, new Message($this));

        $this->definition = new CommandDefinition($command::getSignature(), $command::getDescription());

        $this->parse();

        $this->dispatcher->trigger(ConsoleEvent::CONSOLE_INPUT_AFTER_PARSE->value, new Message($this));

        $this->validate();

        $this->setDefaults();
    }

    /**
     * Регистрация вызванной опции
     *
     * @param string $option имя опции
     * @return void
     */
    private function parseOption(string $option): void
    {
        $option = substr($option, 2);

        $options = array_merge(array_keys($this->options), $this->definition->getOptions());

        if (in_array($option, $options) === false) {

            throw new \InvalidArgumentException(sprintf('Опция "--%s" не существует', $option));
        }

        $this->options[$option] = true;
    }

    /**
     * Установка значения для аргумента
     *
     * @param string $name имя аргумента
     * @param null|string $value значение аргумента
     * @return void
     */
    public function setArgumentValue(string $name, null|string $value): void
    {
        $this->arguments[$name] = is_numeric($value) ? (int) $value : $value;
    }

    /**
     * Регистрация вызванного аргумента
     *
     * @param string $arg введенный аргумент
     * @return void
     */
    private function parseArgument(string $arg): void
    {
        foreach ($this->definition->getArguments() as $name) {

            if (isset($this->arguments[$name]) === true) {
                continue;
            }

            $this->setArgumentValue($name, $arg);
            return;
        }

        throw new \RuntimeException('Слишком много аргументов. Ожидается аргументов: ' . count($this->arguments));
    }

    /**
     * Проверка наличия зарегистрированного аргумента
     *
     * @param string $name имя аргумента
     * @return void
     */
    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    /**
     * Получить значение аргумента
     *
     * @param string $name имя аргумента
     * @return void
     */
    public function getArgument(string $name): int|string
    {
        if (array_key_exists($name, $this->arguments) === false) {
            throw new \InvalidArgumentException(sprintf('Аргумент "%s" не существует', $name));
        }

        return $this->arguments[$name];
    }

    public function getOption(string $name): int|string
    {
        if (array_key_exists($name, $this->options) === false) {
            throw new \InvalidArgumentException(sprintf('Аргумент "%s" не существует', $name));
        }

        return $this->options[$name];
    }

    /**
     * Регистрация опции по-умолчанию
     *
     * @param string $name имя опции
     * @param string $description описание действия опции
     * @return void
     */
    public function addDefaultOption(string $name, string $description): void
    {
        $this->defaultOptions[$name] = [
            'description' => $description,
        ];
    }

    /**
     * Проверка наличия установленной опции вызова
     *
     * @param string $name имя опции
     * @return void
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options) && (bool) $this->options[$name] === true;
    }

    /**
     * Получить опции по умолчанию
     *
     * @return void
     */
    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    /**
     * Принудительно активировать опцию вызова команды
     *
     * @param string $name имя опции
     * @return void
     */
    public function enableOption(string $name): void
    {
        $this->options[$name] = true;
    }
}
