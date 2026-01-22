<?php

namespace framework\logger;

enum LogLevel: string
{
    case EMERGENCY = 'emergency';
    case CRITICAL = 'critical';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    case DEBUG = 'debug';

    /**
     * Возвращает числовое значение уровня (bitmask)
     */
    public function getValue(): int
    {
        return match ($this) {
            self::EMERGENCY => 0x01,
            self::CRITICAL  => 0x04,
            self::ERROR     => 0x08,
            self::WARNING   => 0x10,
            self::INFO      => 0x40,
            self::DEBUG     => 0x80,
            default         => 0x40,
        };
    }

    /**
     * Создаёт LogLevel из строки (например, 'error')
     */
    public static function fromString(string $name): self
    {
        return match (strtolower($name)) {
            'emergency', 'emerg' => self::EMERGENCY,
            'critical', 'crit'   => self::CRITICAL,
            'error', 'err'       => self::ERROR,
            'warning', 'warn'    => self::WARNING,
            'info'               => self::INFO,
            'debug'              => self::DEBUG,
            default              => self::INFO,
        };
    }

    public static function fromValue(int $value): self
    {
        return match (true) {
            $value >= 0x80 => self::DEBUG,
            $value >= 0x40 => self::INFO,
            $value >= 0x10 => self::WARNING,
            $value >= 0x08 => self::ERROR,
            $value >= 0x04 => self::CRITICAL,
            $value >= 0x01 => self::EMERGENCY,
            default        => self::INFO,
        };
    }
}