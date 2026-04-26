<?php

namespace jujelitsa\framework\validate;

use jujelitsa\framework\container\ContainerInterface;
use jujelitsa\framework\validate\ValidateException;
use jujelitsa\framework\validate\ValidateNotFoundException;
use jujelitsa\framework\validate\rules\IntegerRule;
use jujelitsa\framework\validate\rules\FloatRule;
use jujelitsa\framework\validate\rules\StringRule;
use jujelitsa\framework\validate\rules\BooleanRule;
use jujelitsa\framework\validate\rules\RequiredRule;
use jujelitsa\framework\validate\rules\UniqueRule;

class Validator
{
    private array $rules = [];
    private ?ContainerInterface $container = null;
    
    public function __construct(array $customRules = [], ?ContainerInterface $container = null)
    {
        $this->container = $container;
        
        $defaultRules = [
            RuleEnum::INTEGER->value => IntegerRule::class,
            RuleEnum::FLOAT->value => FloatRule::class,
            RuleEnum::STRING->value => StringRule::class,
            RuleEnum::BOOLEAN->value => BooleanRule::class,
            RuleEnum::REQUIRED->value => RequiredRule::class,
            RuleEnum::UNIQUE->value => UniqueRule::class,
        ];
        
        $this->rules = array_merge($defaultRules, $customRules);
    }
    
    public function addRule(string $type, string $ruleClass): self
    {
        $this->rules[$type] = $ruleClass;
        return $this;
    }
    
    public function validate(mixed $value, string|array $rule): void
    {
        $options = [];
        
        if (is_array($rule) === true) {
            $ruleName = $rule[0] ?? throw new ValidateException('Правило валидации не указано');
            $options = array_slice($rule, 1, null, true);
        } 
        if (is_array($rule) === false) {
            $ruleName = $rule;
        }

        $ruleObject = $this->getRule($ruleName);
        
        if ($ruleObject->validate($value, $options) === false) {
            throw new ValidateException($ruleObject->getErrorMessage((string)$value));
        }
    }
    
    public function hasRule(string $type): bool
    {
        return isset($this->rules[$type]) === true;
    }
    
    private function getRule(string $type): RuleInterface
    {
        $ruleClass = $this->rules[$type] ?? null;
        
        if ($ruleClass === null) {
            throw new ValidateNotFoundException("Неизвестное правило валидации: {$type}");
        }
        
        if ($this->container !== null && $this->container->has($ruleClass) === true) {
            $rule = $this->container->get($ruleClass);
            
            if ($rule instanceof RuleInterface === false) {
                throw new ValidateException(
                    "Класс {$ruleClass} должен реализовывать интерфейс " . RuleInterface::class
                );
            }
            
            return $rule;
        }
        
        if (class_exists($ruleClass) == false) {
            throw new ValidateNotFoundException(
                "Класс правила валидации '{$ruleClass}' для типа '{$type}' не найден"
            );
        }
        
        if (is_subclass_of($ruleClass, RuleInterface::class) === false) {
            throw new ValidateException(
                "Класс {$ruleClass} должен реализовывать интерфейс " . RuleInterface::class
            );
        }
        
        return new $ruleClass();
    }
}