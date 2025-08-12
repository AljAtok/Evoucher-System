<?php


namespace RabbitORM;


use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;


class DefinitionReader {

    public function getPropertyDefinition(ReflectionProperty $property, ReflectionClass $class) {

        $jsonDefinition = $class->getConstant($property->getName() . "Definition");
        return json_decode($jsonDefinition);
    }

    public function getClassDefinition(ReflectionClass $class) {

        $jsonDefinition = $this->isDefinitionExist($class, $class->getName());

        if(!$jsonDefinition) $jsonDefinition = $this->isDefinitionExist($class, strtolower($class->getName()));

        return json_decode($jsonDefinition);
   	}

   	private function isDefinitionExist(ReflectionClass $class, $definition) {
       return $class->getConstant($definition . "Definition") ?: FALSE;
   	}


}