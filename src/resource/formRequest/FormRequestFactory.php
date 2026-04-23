<?php

namespace jujelitsa\framework\resource\formRequest;

use Psr\Http\Message\ServerRequestInterface;
use jujelitsa\framework\container\ContainerInterface;

final class FormRequestFactory implements FormRequestFactoryInterface
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ContainerInterface $container
    ) {}

    public function create(string $formClassName, array $rules = []): FormRequestInterface
    {
        if (class_exists($formClassName) === false) {
            throw new \InvalidArgumentException("Класс формы {$formClassName} не найден");
        }

        $form = $this->container->get($formClassName);

        if ($form instanceof FormRequestInterface === false) {
            throw new \InvalidArgumentException("Класс {$formClassName} должен реализовывать FormRequestInterface");
        }

        if (empty($rules) === false) {
            foreach ($rules as $field => $fieldRules) {
                $form->addRule([$field], $fieldRules);
            }
        }

        $parsedBody = $this->request->getParsedBody() ?? [];

        foreach ($form->getFields() as $field) {
            if (array_key_exists($field, $parsedBody)) {
                $form->setValue($field, $parsedBody[$field]);
            }
        }

        return $form;
    }
}