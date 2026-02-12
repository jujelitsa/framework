# Changelog

В этом файле ведется учет изменений проекта

Формат основан на [стандарте формата CHANGELOG](https://keepachangelog.com/en/1.0.0/),
и придерживается [правил версионирования](https://semver.org/spec/v2.0.0.html).

## [ [0.2.3](https://github.com/jujelitsa/framework/releases/tag/0.2.3) ] - 12.02.2026

- Изменено:
  - Способ получение API_AUTH_KEY в миделвеере XApiKeyMiddleware
- Исправлено:
  - Приоритет разрешения зависимостей в DI
  - DebugStorage, ConfigStorage и исключения распределены по соответствующим директориям

## [ [0.2.2](https://github.com/jujelitsa/framework/releases/tag/0.2.2) ] - 06.02.2026

- Реализовано:
  - Класс ConfigStorage
- Исправлено:
  - Метод parsedBody в ServerRequest

## [ [0.2.2](https://github.com/jujelitsa/framework/releases/tag/0.2.2) ] - 06.02.2026

- Реализовано:
  - Класс ConfigStorage
- Исправлено:
  - Метод parsedBody в ServerRequest

## [ [0.2.1](https://github.com/jujelitsa/framework/releases/tag/0.2.1) ] - 25.01.2026

- Исправлено:
  - Ошибка в X-API-KEY авторизации

## [ [0.2.0](https://github.com/jujelitsa/framework/releases/tag/0.2.0) ] - 25.01.2026

- Реализовано:
  - X-API-KEY авторизация
- Исправлено:
  - Опечатка json ошибки
  - Обработка статуса запроса в HTTPKernel
  - Обработка путсого тела ответа в HTTPKernel
  - Страница ошибки http

## [ [0.1.1](https://github.com/jujelitsa/framework/releases/tag/0.1.1) ] - 22.01.2026

- Исправлено:
  - Неймспейсы классов

## [ [0.1.0](https://github.com/jujelitsa/framework/releases/tag/0.1.0) ] - 22.01.2026

- Реализовано:
  - Перенесен в пакет основной функционал фреймворка
  - Файл README.md
  - Файл composer.json
  - Файл .gitignore
   