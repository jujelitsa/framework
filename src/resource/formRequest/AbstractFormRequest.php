<?php

namespace jujelitsa\framework\resource\formRequest;

use InvalidArgumentException;
use jujelitsa\framework\validate\ValidateException;
use jujelitsa\framework\validate\Validator;

abstract class AbstractFormRequest implements FormRequestInterface
{
    protected bool $skipEmptyValues = false;
    private array $errors = [];
    private array $values = [];
    private array $dynamicRules = [];

    public function __construct(
        private readonly Validator $validator,
    ) {}

    /**
     * Возврат правил валидации формы
     *
     * @return array
     * Пример:
     * [
     *     [['name'], 'required'],
     *     [['name'], 'string'],
     *     [['email'], 'required'],
     *     [['email'], 'email'],
     * ]
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Динамическая установка правил валидации
     *
     * @param array $attributes
     * @param array|string $rule
     * @return void
     * Пример:
     * $form->addRule(['name'], 'required');
     * $form->addRule(['name'], ['string', 'min' => 3]);
     */
    public function addRule(array $attributes, array|string $rule): void
    {
        $this->dynamicRules[] = [$attributes, $rule];
    }

    /**
     * Валидация формы
     *
     * @throws InvalidArgumentException|ValidateException
     */
    public function validate(): void
    {
        $values = $this->getValues();

        foreach ($this->getRules() as $rule) {
            if (count($rule) !== 2) {
                throw new InvalidArgumentException('Правила должны быть заданы в формате [[аттрибуты], правило]');
            }

            $this->validateByRule($values, $rule[0], $rule[1]);
        }
    }

    /**
     * Валидация по одному правилу
     *
     * @param array $values
     * @param array $attributes
     * @param array|string $rule
     */
    private function validateByRule(array $values, array $attributes, array|string $rule): void
    {
        $ruleName = is_array($rule) ? $rule[0] : $rule;
        
        if ($ruleName === 'unique') {
            if (count($attributes) === 1) {
                $value = $this->getValueToValidate($attributes[0], $values);
                
                if ($this->skipEmptyValues === true && ($value === '' || $value === null)) {
                    return;
                }
                
                try {
                    $ruleWithTarget = $rule;
                    if (is_array($ruleWithTarget)) {
                        $ruleWithTarget['target'] = $attributes[0];
                    }
                    $this->validator->validate($value, $ruleWithTarget);
                } catch (ValidateException $e) {
                    $this->addError($attributes[0], $e->getMessage());
                }
            } 
            if (count($attributes) !== 1) {
                $allEmpty = true;
                foreach ($attributes as $attribute) {
                    if (empty($values[$attribute]) === false) {
                        $allEmpty = false;
                        break;
                    }
                }
                
                if ($allEmpty === true) {
                    return;
                }
                
                try {
                    $compositeValue = [];
                    foreach ($attributes as $attribute) {
                        $compositeValue[$attribute] = $values[$attribute] ?? null;
                    }
                    $ruleWithTarget = $rule;
                    if (is_array($ruleWithTarget) === true && isset($ruleWithTarget['target']) === false) {
                        $ruleWithTarget['target'] = $attributes;
                    }
                    $this->validator->validate($compositeValue, $ruleWithTarget);
                } catch (ValidateException $e) {
                    $this->addError($attributes[0], $e->getMessage());
                }
            }
            return;
        }
        
        foreach ($attributes as $attribute) {
            $value = $this->getValueToValidate($attribute, $values);

            if ($this->skipEmptyValues === true && ($value === '' || $value === null)) {
                continue;
            }

            try {
                $this->validator->validate($value, $rule);
            } catch (ValidateException $e) {
                $this->addAttributesError($attribute, $e->getMessage());
            }
        }
    }

    /**
     * Получение значения для валидации
     *
     * @param string|array $attribute
     * @param array $values
     * @return mixed
     */
    private function getValueToValidate(string|array $attribute, array $values): mixed
    {
        if (is_string($attribute) === true) {
            return $values[$attribute] ?? null;
        }

        $valuesList = [];

        foreach ($attribute as $attributeName) {
            $valuesList[] = $values[$attributeName] ?? null;
        }

        return $valuesList;
    }

    /**
     * Добавление ошибки для атрибута
     *
     * @param string $attribute
     * @param string $message
     */
    public function addError(string $attribute, string $message): void
    {
        if (isset($this->errors[$attribute]) === false) {
            $this->errors[$attribute] = [];
        }

        $this->errors[$attribute][] = $message;
    }

    /**
     * Добавление ошибки для атрибута(ов)
     *
     * @param array|string $attributes
     * @param string $message
     */
    private function addAttributesError(array|string $attributes, string $message): void
    {
        if (is_string($attributes) === true) {
            $this->addError($attributes, $message);
            return;
        }

        foreach ($attributes as $attribute) {
            $this->addError($attribute, $message);
        }
    }

    /**
     * Возврат всех ошибок валидации
     *
     * @return array
     * Пример:
     * [
     *     'name' => ['Поле name обязательно'],
     *     'email' => ['Поле email должно быть валидным email адресом'],
     * ]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Проверка наличия ошибок
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return empty($this->errors) === false;
    }

    /**
     * Установка флага пропуска пустых значений
     */
    public function setSkipEmptyValues(): void
    {
        $this->skipEmptyValues = true;
    }

    /**
     * Возврат значений формы
     *
     * @return array
     * Пример:
     * [
     *     "id" => 1,
     *     "order_id" => 3,
     *     "name" => "Некоторое имя 1"
     * ]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Установка значения поля
     *
     * @param string $name
     * @param mixed $value
     */
    public function setValue(string $name, mixed $value): void
    {
        $this->values[$name] = $value;
    }

    /**
     * Установка нескольких значений полей
     *
     * @param array $values
     */
    public function setValues(array $values): void
    {
        foreach ($values as $name => $value) {
            $this->setValue($name, $value);
        }
    }

    /**
     * Возврат списка всех полей, участвующих в валидации
     *
     * @return array
     */
    public function getFields(): array
    {
        $rules = $this->getRules();
        
        if (empty($rules)) {
            return [];
        }
        
        $fields = array_column($rules, 0);
        $flattenFields = array_merge(...$fields);
        
        return array_unique(array_filter($flattenFields, 'is_string'));
    }

    /**
     * Получение всех правил (статических + динамических)
     *
     * @return array
     */
    protected function getRules(): array
    {
        return array_merge($this->rules(), $this->dynamicRules);
    }

    /**
     * Очистка ошибок
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Очистка всех правил
     */
    public function clearRules(): void
    {
        $this->dynamicRules = [];
    }

    /**
     * Проверка, есть ли значение для поля
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->values[$name]);
    }

    /**
     * Получение значения поля с дефолтом
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->values[$name] ?? $default;
    }
}