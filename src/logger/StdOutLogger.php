<?php

namespace framework\logger;

class StdOutLogger extends AbstractLogger
{
    public function __construct(private LogStateProcessor $stateProcessor) {}

    protected function formatMessage(string $level, mixed $message): string
    {

        $dto = $this->stateProcessor->process([$message, $level]);

        $orderedData = [
            'index' => $dto->index,
            'category' => $dto->category,
            'context' =>  implode(':', $dto->context),
            'level' => $dto->level,
            'level_name' => $dto->level_name,
            'action' => $dto->action,
            'action_type' => $dto->action_type,
            'datetime' => $dto->datetime,
            'timestamp' => gmdate('Y-m-d\TH:i:s.u') . 'Z',
            'userId' => $dto->userId,
            'ip' => $dto->ip,
            'real_ip' => $dto->real_ip,
            'x_debug_tag' => $dto->x_debug_tag,
            'message' => $dto->message,
            'exception' => $dto->exception,
            'extras' => $dto->extras,
        ];

        return json_encode($orderedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function writeLog(string $log): void
    {
        file_put_contents('php://stderr', $log . PHP_EOL);
    }

}
