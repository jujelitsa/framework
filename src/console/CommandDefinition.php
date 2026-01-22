<?php

namespace framework\console;

/**
 * Хранение описания вызванной команды
 */
class CommandDefinition
{
    /**
     * Информация о вызванной команде: имя, описание
     * @var array
     */
    private array $commandInfo = [
        'name' => null,
        'description' => null,
    ];

    /**
     * Аргументы команды
     * @var array
     */
    private array $arguments = [];

    /**
     * Опции команды
     * @var array
     */
    private array $options = [];

    public function __construct(string $signature, string $description)
    {
        $this->initDefinitions($signature);
        $this->commandInfo['description'] = $description;
    }

    /**
     * Возврат имен аргументов команды
     *
     * @return array
     */
    public function getArguments(): array
    {
        return array_keys($this->arguments);
    }

    /**
     * Возврат имен опций команды
     *
     * @return array
     */
    public function getOptions(): array
    {
        return array_keys($this->options);
    }

    /**
     * Возврат имени вызванной команды
     *
     * @return string
     */
    public function getCommandName(): string
    {
        return $this->commandInfo['name'];
    }

    /**
     * Возврат описания вызванной команды
     *
     * @return string
     */
    public function getCommandDescription(): string
    {
        return $this->commandInfo['description'];
    }

    /**
     * Возврат параметров, определенных для аргумента:
     * описание, обязательный да/нет, значение по умолчанию
     *
     * @param  string $name имя аругмента
     * @return array
     */
    public function getArgumentDefinition(string $name): array
    {
        return $this->arguments[$name];
    }

    /**
     * Возврат параметров, определенных для опции:
     * описание
     *
     * @param  string $name имя опции
     * @return array
     */
    public function getOptionDefinition(string $name): array
    {
        return $this->options[$name];
    }

    /**
     * Определение аргумента, установленного обязательным
     *
     * @param  string $name имя аргумента
     * @return bool
     */
    public function isRequired(string $name): bool
    {
        return $this->arguments[$name]['required'];
    }

    /**
     * Возврат значения по умолчанию,
     * установленного для аргумента
     *
     * @param  string $name имя аргумента
     * @return mixed
     */
    public function getDefaultValue(string $name): mixed
    {
        return $this->arguments[$name]['default'];
    }

    /**
     * Формирование параметров, определенных для опций и аргументов
     *
     * @param  string $signature строка описания команды
     * @return void
     */
    private function initDefinitions(string $signature): void
    {
        $matches = [];

        if ((bool) preg_match('/^([\w\S]+)/', $signature, $matches) === false) {
            throw new \InvalidArgumentException("Не удалось определить имя команды из сигнатуры: '$signature'");
        }

        $this->commandInfo['name'] = $matches[1];

        if ((bool) preg_match_all('/{\s*(.*?)\s*}/', $signature, $matches) === false) {
            return;
        }

        foreach ($matches[1] as $param) {
            $param = trim($param);
            if ((bool) preg_match('/--(.*)/', $param, $optMatches) === true) {
                $this->initOption($optMatches[0]);
                continue;
            }

            $this->initArgument($param);
        }
    }

    /**
     * Определение параметров, определенных для опций
     *
     * @param  string $option строка зарегистрированной опции
     * @return void
     */
    private function initOption(string $option): void
    {
        $name = ltrim($option, '-');

        if (isset($this->options[$name]) === true) {
            throw new \InvalidArgumentException('Опция --' . $name . 'указана повторно');
        }

        preg_match('/:(.*)$/', $option, $matches);

        $this->options[$name] = [
            'description' => $matches[1] ?? null,
        ];

    }

    /**
     * Определение параметров, определенных для аргументов
     *
     * @param  string $arg строка зарегистрированного аргумента
     * @return void
     */
    private function initArgument(string $arg): void
    {
        $matches = [];

        preg_match("/^\??(.+?)\b/", $arg, $matches);
        $argName = $matches[1];

        if (isset($this->arguments[$argName]) === true) {
            throw new \InvalidArgumentException("Опция '--$argName' указана повторно");
        }

        $argRequired = ((bool) preg_match("/\?/", $arg)) === false;

        preg_match("/:(.*?)(\||$)/", $arg, $matches);
        $argDescription = $matches[1] ?? null;

        preg_match("/(?<==)[\wA-z-+]+/", $arg, $matches);
        $argDefault = $matches[0] ?? null;

        $this->arguments[$argName] = [
            'required' => $argRequired,
            'description' => $argDescription,
            'default' => $argDefault,
        ];
    }
}
