<?php
/**
 * @var Throwable $exception
 * @var string $envMode
 * @var string $debugTag
 * @var bool $debugMode
 */

use framework\EnvironmentModeEnum;

if ($exception->getStatusCode() >= 400 && $exception->getStatusCode() < 500) {
    $title =
        "Запрос не может быть обработан\n" .
        "Ошибка: {$exception->getStatusCode()}\n" .
        $exception->getMessage();
    $message =  "идентификатор сеанса: {$debugTag}";
}

if ($exception->getStatusCode() >= 500) {
    $title =
        "Запрос не может быть обработан\n".
        "Произошла внутренняя ошибка сервера";
    $message =
        "Обратитесь к администратору системы\n".
        "support@efko.ru\n".
        "В запросе укажите идентификатор сеанса\n".
        "идентификатор сеанса: {$debugTag}";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ошибка</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eceded;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .block {
            background: #fdb1c7;
            padding: 20px 25px;
            margin: 35px;
            box-sizing: border-box;
        }

        .title {
            font-size: 24px;
        }

        .message {
            font-size: 16px;
        }

        .trace-title {
            font-size: 24px;
            font-weight: bold;
            /*margin-bottom: 15px;*/
            margin: 0 35px 15px 35px;
            white-space: pre-wrap;
        }

        .trace {
            background: #fdb1c7;
            padding: 20px 25px;
            margin: 0 35px 35px 35px;
            font-size: 15px;
            white-space: pre-wrap;
            overflow-x: auto;
            /*max-width: 1100px;*/
            box-sizing: border-box;
        }
    </style>
</head>
<body>

<div class="block">
    <div class="title"><?= nl2br(htmlspecialchars($title, ENT_QUOTES, 'UTF-8')) ?></div>
    <br>
    <br>
    <div class="message">
        <?= nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "\n" ?>
    </div>

</div>

<?php if ($envMode === EnvironmentModeEnum::DEVELOPMENT->value && $debugMode === true): ?>
    <div class="trace-title">Трейс вызова</div>
    <div class="trace"><?= htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

</body>
</html>
