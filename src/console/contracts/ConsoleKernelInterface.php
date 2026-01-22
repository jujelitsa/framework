<?php

namespace framework\console\contracts;

interface ConsoleKernelInterface
{
    /**
     * Возврат имени приложения
     *
     * @return string
     */
    public function getAppName(): string;

    /**
     * Возврат версии приложения
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Возврат карты команд
     *
     * @return string
     */
    public function getCommands(): array;

    /**
     * Регистрация неймспейсов команд
     *
     * @return string
     */
    public function registerCommandNamespaces(array $commandNameSpaces): void;

    /**
     * Обработка запроса
     *
     * @return string
     */
    public function handle(): int;

    public function terminate(int $status): never;

}