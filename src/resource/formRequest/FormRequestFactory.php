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

    public function create(string $formClassName): FormRequestInterface
    {
        if (class_exists($formClassName) === false) {
            throw new \InvalidArgumentException("Класс формы {$formClassName} не найден");
        }

        $form = $this->container->get($formClassName);

        if ($form instanceof FormRequestInterface === false) {
            throw new \InvalidArgumentException("Класс {$formClassName} должен реализовывать FormRequestInterface");
        }

        if ($this->request !== null) {
            $parsedBody = $this->request->getParsedBody() ?? [];
            if (method_exists($form, 'setValues') === true) {
                $form->setValues($parsedBody);
            }
        }

        return $form;
    }
}
