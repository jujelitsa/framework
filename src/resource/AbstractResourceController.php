<?php

namespace jujelitsa\framework\resource;

use jujelitsa\framework\resource\ResourceDataFilterInterface;
use Psr\Http\Message\ServerRequestInterface;
use jujelitsa\framework\resource\formRequest\FormRequestFactoryInterface;
use jujelitsa\framework\resource\enum\ResourceActionTypesEnum;
use jujelitsa\framework\resource\formRequest\FormRequestInterface;
use jujelitsa\framework\http\Exception\HttpBadRequestException;
use jujelitsa\framework\http\Exception\HttpForbiddenException;
use jujelitsa\framework\http\Exception\HttpNotFoundException;
use jujelitsa\framework\event_dispatcher\EventDispatcherInterface;
use jujelitsa\framework\event_dispatcher\Message;
use jujelitsa\framework\http\response\JsonResponse;
use jujelitsa\framework\http\response\CreateResponse;
use jujelitsa\framework\http\response\DeleteResponse;
use jujelitsa\framework\http\response\PatchResponse;
use jujelitsa\framework\http\response\UpdateResponse;
use jujelitsa\framework\logger\LoggerInterface;
use jujelitsa\framework\resource\formRequest\FormRequest;

abstract class AbstractResourceController
{
    public function __construct(
        protected ResourceDataFilterInterface $resourceDataFilter,
        protected ServerRequestInterface $request,
        protected FormRequestFactoryInterface $formRequestFactory,
        protected ResourceWriterInterface $resourceWriter,
        protected EventDispatcherInterface $eventDispatcher,
        protected LoggerInterface $logger,
    ) {
        $this->resourceDataFilter
            ->setResourceName($this->getResourceName())
            ->setAccessibleFields($this->getAccessibleFields())
            ->setAccessibleFilters($this->getAccessibleFilters())
            ->setRelationships($this->getRelationships());

        $this->resourceWriter
            ->setResourceName($this->getResourceName())
            ->setAccessibleFields($this->getAccessibleFields());
    }

    protected function getForms(): array
    {
        return [
            ResourceActionTypesEnum::CREATE->value => [FormRequest::class, $this->getFieldRules()],
            ResourceActionTypesEnum::UPDATE->value => [FormRequest::class, $this->getFieldRules()],
            ResourceActionTypesEnum::PATCH->value => [FormRequest::class, $this->getFieldRules()],
        ];
    }

    protected function getAvailableActions(): array
    {
        return [
            ResourceActionTypesEnum::INDEX,
            ResourceActionTypesEnum::VIEW,
            ResourceActionTypesEnum::CREATE,
            ResourceActionTypesEnum::UPDATE,
            ResourceActionTypesEnum::PATCH,
            ResourceActionTypesEnum::DELETE,
        ];
    }

    protected function getFieldRules(): array
    {
        return [];
    }

    abstract protected function getResourceName(): string;

    /**
     * Возврат имен свойств ресурса, доступных к чтению
     * Пример запроса:
     * ?fields=id,order_id,name
     * @return array
     */
    abstract protected function getAccessibleFields(): array;
    
    /**
     * Возврат имен свойств ресурса, доступных к фильтрации
     * Пример запроса:
     * ?filter[order_id][$eq]=3
     * @return array
     */
    abstract protected function getAccessibleFilters(): array;

    abstract protected function getRelationships(): array;

    /**
     * @throws HttpForbiddenException
     */
    private function checkCallAvailability(ResourceActionTypesEnum $actionType): void
    {
        if (in_array($actionType, $this->getAvailableActions(), true) === false) {
            throw new HttpForbiddenException("Вызов метода {$actionType->value} запрещен");
        }
    }
    
    /**
     * Возврат ресурсов, по ограничениям указанным в строке запроса
     * 
     * Пример запроса:
     * ?fields[]=id&fields[]=order_id&fields[]=name&filter[order_id][$eq]=3
     * Пример ответа:
     * application/json
     * [
     *     {
     *         "id": 1,
     *         "order_id":3,
     *         "name": "Некоторое имя 1"
     *     },
     *     {
     *         "id": 2,
     *         "order_id":3,
     *         "name": "Некоторое имя 2"
     *     },
     *     ...
     * ]
     * @return JsonResponse
     */
    public function actionList(): JsonResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::INDEX);

        $resourceName = $this->getResourceName();
        $queryParams = $this->request->getQueryParams();

        $this->eventDispatcher->trigger(ResourceEvent::BEFORE_LIST->value, new Message([
            'resource' => $resourceName,
            'query' => $queryParams,
        ]));
        
        $this->logger->info("Список ресурсов {$resourceName} запрошен");

        $data = $this->resourceDataFilter->filterAll($queryParams);

        if (empty($queryParams['filter']) === false && empty($data) === true) {
            throw new HttpNotFoundException('Данные не найдены');
        }
        
        $this->eventDispatcher->trigger(ResourceEvent::AFTER_LIST->value, new Message([
            'resource' => $resourceName,
            'count' => count($data),
            'query' => $queryParams,
            'data' => $data,
        ]));
        
        return new JsonResponse($data);
    }

    /**
     * Возврат ресурса, по ограничениям указанным в строке запроса
     * 
     * Пример запроса:
     * ?fields[]=id&fields[]=name&filter[id][$eq]=1
     * Пример ответа:
     * application/json
     * {
     *     "id": 1,
     *     "name": "Некоторое имя 1"
     * },
     * @return JsonResponse
     */
    public function actionView(string|int $id): JsonResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::VIEW);

        $resourceName = $this->getResourceName();
        $queryParams = $this->request->getQueryParams();
        $queryParams['filter']['id'] = ['$eq' => $id];
        
        $this->eventDispatcher->trigger(ResourceEvent::BEFORE_VIEW->value, new Message([
            'resource' => $resourceName,
            'id' => $id,
            'query' => $queryParams,
        ]));
        
        $this->logger->info("Просмотр ресурса {$resourceName} запрошен", ['id' => $id]);

        $data = $this->resourceDataFilter->filterOne($queryParams);
        
        $this->eventDispatcher->trigger(ResourceEvent::AFTER_VIEW->value, new Message([
            'resource' => $resourceName,
            'id' => $id,
            'found' => $data !== null,
            'data' => $data,
        ]));
        
        return new JsonResponse($data);
    }

    public function actionCreate(): CreateResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::CREATE);

        $resourceName = $this->getResourceName();
        $this->logger->info("Создание ресурса {$resourceName} начато");

        $form = $this->buildForm(ResourceActionTypesEnum::CREATE->value);

        $this->eventDispatcher->trigger(ResourceEvent::BEFORE_CREATE->value, new Message([
            'resource' => $resourceName,
            'values' => $form->getValues(),
        ]));
            
        $form->validate();

        if (empty($form->getErrors()) === false) {
            $this->eventDispatcher->trigger(ResourceEvent::VALIDATION_ERROR->value, new Message([
                'resource' => $resourceName,
                'errors' => $form->getErrors(),
            ]));    
            throw new HttpBadRequestException(json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        try {
            $createdId = $this->resourceWriter->create($form->getValues());
            
            $this->logger->info("Ресурс {$resourceName} создан", ['id' => $createdId]);
            
            $this->eventDispatcher->trigger(ResourceEvent::AFTER_CREATE->value, new Message([
                'resource' => $resourceName,
                'id' => $createdId,
                'values' => $form->getValues(),
            ]));
            
            return new CreateResponse($createdId);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error("Ошибка при создании ресурса {$resourceName}", [
                'error' => $exception->getMessage(),
            ]);
            throw new HttpBadRequestException($exception->getMessage());
        }
    }

    public function actionUpdate(string|int $id): UpdateResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::UPDATE);

        $resourceName = $this->getResourceName();
        $this->logger->info("Обновление ресурса {$resourceName} начато", ['id' => $id]);

        $form = $this->buildForm(ResourceActionTypesEnum::UPDATE->value);

        $this->eventDispatcher->trigger(ResourceEvent::BEFORE_UPDATE->value, new Message([
            'resource' => $resourceName,
            'id' => $id,
            'values' => $form->getValues(),
        ]));

        $form->validate();

        if (empty($form->getErrors()) === false) {
            $this->eventDispatcher->trigger(ResourceEvent::VALIDATION_ERROR->value, new Message([
                'resource' => $resourceName,
                'id' => $id,
                'errors' => $form->getErrors(),
            ]));
            throw new HttpBadRequestException(json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        try {
            $rowsCount = $this->resourceWriter->update($id, $form->getValues());
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error("Ошибка при обновлении ресурса {$resourceName}", [
                'id' => $id,
                'error' => $exception->getMessage(),
            ]);
            throw new HttpBadRequestException($exception->getMessage());
        }

        if ($rowsCount === 0) {
            $this->logger->warning("Ресурс {$resourceName} для обновления не найден", ['id' => $id]);
            
            $this->eventDispatcher->trigger(ResourceEvent::NOT_FOUND->value, new Message([
                'resource' => $resourceName,
                'id' => $id,
                'action' => 'update',
            ]));
            
            throw new HttpNotFoundException();
        }

        $this->logger->info("Ресурс {$resourceName} обновлен", ['id' => $id]);
        
        $this->eventDispatcher->trigger(ResourceEvent::AFTER_UPDATE->value, new Message([
            'resource' => $resourceName,
            'id' => $id,
            'values' => $form->getValues(),
        ]));

        return new UpdateResponse();
    }

    public function actionPatch(string|int $id): PatchResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::PATCH);
        
        $resourceName = $this->getResourceName();
        $this->logger->info("Частичное обновление ресурса {$resourceName} начато", ['id' => $id]);
        
        $form = $this->buildForm(ResourceActionTypesEnum::PATCH->value);
        $form->setSkipEmptyValues();

        $this->eventDispatcher->trigger(ResourceEvent::BEFORE_PATCH->value, new Message([
            'resource' => $resourceName,
            'id' => $id,
            'values' => $form->getValues(),
        ]));

        $form->validate();

        if (empty($form->getErrors()) === false) {
            $this->eventDispatcher->trigger(ResourceEvent::VALIDATION_ERROR->value, new Message([
                'resource' => $resourceName,
                'id' => $id,
                'errors' => $form->getErrors(),
            ]));
            throw new HttpBadRequestException(json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        try {
            $rowsCount = $this->resourceWriter->patch($id, $form->getValues());
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error("Ошибка при частичном обновлении ресурса {$resourceName}", [
                'id' => $id,
                'error' => $exception->getMessage(),
            ]);
            throw new HttpBadRequestException($exception->getMessage());
        }

        if ($rowsCount === 0) {
            $this->logger->warning("Ресурс {$resourceName} для частичного обновления не найден", ['id' => $id]);
            
            $this->eventDispatcher->trigger(ResourceEvent::NOT_FOUND->value, new Message([
                'resource' => $resourceName,
                'id' => $id,
                'action' => 'patch',
            ]));
            
            throw new HttpNotFoundException();
        }

        $this->logger->info("Ресурс {$resourceName} частично обновлен", ['id' => $id]);
        
        $this->eventDispatcher->trigger(ResourceEvent::AFTER_PATCH->value, new Message([
            'resource' => $resourceName,
            'id' => $id,
            'values' => $form->getValues(),
        ]));

        return new PatchResponse();
    }

    public function actionDelete(string|int $id): DeleteResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::DELETE);
        
        $resourceName = $this->getResourceName();

        $this->eventDispatcher->trigger(ResourceEvent::BEFORE_DELETE->value, new Message([
            'resource' => $resourceName,
            'id' => $id,
        ]));

        $this->logger->info("Удаление ресурса {$resourceName} начато", ['id' => $id]);
        
        try {
            $rowsCount = $this->resourceWriter->delete($id);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error("Ошибка при удалении ресурса {$resourceName}", [
                'id' => $id,
                'error' => $exception->getMessage(),
            ]);
            throw new HttpBadRequestException($exception->getMessage());
        }

        if ($rowsCount === 0) {
            $this->logger->warning("Ресурс {$resourceName} для удаления не найден", ['id' => $id]);
            
            $this->eventDispatcher->trigger(ResourceEvent::NOT_FOUND->value, new Message([
                'resource' => $resourceName,
                'id' => $id,
                'action' => 'delete',
            ]));
            
            throw new HttpNotFoundException();
        }

        $this->logger->info("Ресурс {$resourceName} удален", ['id' => $id]);
        
        $this->eventDispatcher->trigger(ResourceEvent::AFTER_DELETE->value, new Message([
            'resource' => $resourceName,
            'id' => $id,
        ]));

        return new DeleteResponse();
    }

    private function buildForm(string $action): FormRequestInterface
    {
        $formParams = $this->getForms()[$action] ?? null;

        if ($formParams === null) {
            throw new \RuntimeException("Конфигурация формы для действия '{$action}' не найдена");
        }

        if (is_array($formParams) === true && count($formParams) === 2) {
            return $this->formRequestFactory->create($formParams[0], $formParams[1]);
        }

        if (is_string($formParams) === true) {
            return $this->formRequestFactory->create($formParams);
        }

        throw new \RuntimeException("Неверный формат конфигурации формы для действия '{$action}'. ");
    }
}