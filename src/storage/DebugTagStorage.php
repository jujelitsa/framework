<?php

namespace jujelitsa\framework\storage;

class DebugTagStorage implements DebugTagStorageInterface
{
    /**
     * Cтрока значения тега отлаки
     *
     * @var string|null
     */
    private string|null $tag = null;

    /**
     * Получить значение тега
     *
     * @return string
     */
    public function getTag(): string
    {
        if ($this->tag === null) {
            throw new \RuntimeException('Тег отладки не определен');
        }

        return $this->tag;
    }

    /**
     * Установить значение тега
     *
     * @param string $tag
     * @return void
     */
    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }
}
