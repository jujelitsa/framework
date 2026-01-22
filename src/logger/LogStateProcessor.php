<?php

namespace jujelitsa\framework\logger;

use Exception;
use jujelitsa\framework\ApplicationTypeEnum;
use jujelitsa\framework\event_dispatcher\EventDispatcherInterface;
use jujelitsa\framework\event_dispatcher\LogContextEvent;
use jujelitsa\framework\event_dispatcher\Message;
use jujelitsa\framework\storage\DebugTagStorageInterface;
use Psr\Http\Message\ServerRequestInterface;

class LogStateProcessor
{
    private DTO $storage;

    public function __construct(
        string                                    $index,
        string                                    $actionType,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly DebugTagStorageInterface $debugTagStorage,
        private readonly ?ServerRequestInterface  $request = null,
    ) {
        $this->storage = new DTO();
        $this->storage->index = $index;

        $this->storage->action_type = $actionType;

        $this->initEventListeners();
    }

    private function initEventListeners(): void
    {
        $listeners = [
            LogContextEvent::ATTACH_CONTEXT => function (Message $message) {
                $context = $message->getContent();
                $this->storage->context[] = $context;
            },
            LogContextEvent::DETACH_CONTEXT => function (Message $message) {
                $context = $message->getContent();
                $key = array_search($context, $this->storage->context, true);
                if ($key !== false) {
                    unset($this->storage->context[$key]);
                    $this->storage->context = array_values($this->storage->context);
                }
            },
            LogContextEvent::FLUSH_CONTEXT => function () {
                $this->storage->context = [];
            },
            LogContextEvent::ATTACH_EXTRAS => function (Message $message) {
                $data = $message->getContent();
                $this->storage->extras = json_encode($data, JSON_UNESCAPED_UNICODE);
            },
            LogContextEvent::FLUSH_EXTRAS => function () {
                $this->storage->extras = null;
            },
            LogContextEvent::ATTACH_CATEGORY => function (Message $message) {
                $this->storage->category = $message->getContent();
            },
        ];

        foreach ($listeners as $event => $listener) {
            $this->dispatcher->attach($event, $listener);
        }
    }

    private function fillByActionType(DTO $storage): DTO
    {
        if ($this->storage->action_type === ApplicationTypeEnum::WEB->value) {

            $serverParams = $this->request->getServerParams();

            $storage->ip = $serverParams['REMOTE_ADDR'] ?? null;
            $storage->real_ip = $storage->ip;

            if (empty($serverParams['HTTP_X_FORWARDED_FOR']) === false) {
                $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
                $storage->real_ip = trim($ips[0]);
            }

            $storage->action = $this->request->getUri()->getPath();

            return $storage;
        }

        if ($this->storage->action_type === ApplicationTypeEnum::CLI->value) {

            $storage->ip = null;
            $storage->real_ip = null;
            $storage->action = $_SERVER['SCRIPT_NAME'];

            return $storage;
        }

        throw new \RuntimeException("Неизвестный тип вызова: {$this->storage->action_type}");
    }

    /**
     * @param array $logMessageData [message, level, category]
     * @return DTO
     * @throws Exception
     */
    public function process(array $logMessageData): DTO
    {
        $storage = clone $this->storage;

        if (($logMessageData[0] instanceof \Throwable) === true) {

            $storage->exception = [
                'file' => $logMessageData[0]->getFile(),
                'line' => $logMessageData[0]->getLine(),
                'code' => $logMessageData[0]->getCode(),
                'trace' => explode(PHP_EOL, $logMessageData[0]->getTraceAsString()),
            ];
            $storage->message = $logMessageData[0]->getMessage();
        }

        if (($logMessageData[0] instanceof \Throwable) !== true) {
            $storage->message = $logMessageData[0];
        }

        $utc = new \DateTime('now', new \DateTimeZone('UTC'));
        $storage->datetime = $utc->format('Y-m-d\TH:i:s.uP');
        $storage->timestamp = (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.uP');

        $storage = $this->fillByActionType($storage);

        $logLevel = LogLevel::fromString($logMessageData[1]);
        $storage->level = $logLevel->getValue();
        $storage->level_name = $logLevel->value;
        $storage->userId = null;
        $storage->x_debug_tag = $this->debugTagStorage->getTag();

        return $storage;
    }
}
