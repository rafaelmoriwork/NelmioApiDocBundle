<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\ModelDescriber\Annotations;

use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\OpenApiPhp\ModelRegister;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\Util\SetsContextTrait;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use OpenApi\Generator;

/**
 * @internal
 */
class OpenApiAnnotationsReader
{
    use SetsContextTrait;

    private ModelRegister $modelRegister;

    public function __construct(private Reader $annotationsReader, ModelRegistry $modelRegistry, array $mediaTypes)
    {
        $this->modelRegister = new ModelRegister($modelRegistry, $mediaTypes);
    }

    public function updateSchema(\ReflectionClass $reflectionClass, OA\Schema $schema): void
    {
        /** @var OA\Schema $oaSchema */
        if (!$oaSchema = $this->annotationsReader->getClassAnnotation($reflectionClass, OA\Schema::class)) {
            return;
        }

        // Read @Model annotations
        $this->modelRegister->__invoke(new Analysis([$oaSchema], Util::createContext()));

        if (!$oaSchema->validate()) {
            return;
        }

        $schema->mergeProperties($oaSchema);
    }

    public function getPropertyName($reflection, string $default): string
    {
        /** @var OA\Property $oaProperty */
        if ($reflection instanceof \ReflectionProperty && !$oaProperty = $this->annotationsReader->getPropertyAnnotation($reflection, OA\Property::class)) {
            return $default;
        } elseif ($reflection instanceof \ReflectionMethod && !$oaProperty = $this->annotationsReader->getMethodAnnotation($reflection, OA\Property::class)) {
            return $default;
        }

        return Generator::UNDEFINED !== $oaProperty->property ? $oaProperty->property : $default;
    }

    public function updateProperty($reflection, OA\Property $property, array $serializationGroups = null): void
    {
        // In order to have nicer errors
        $declaringClass = $reflection->getDeclaringClass();

        $this->setContext(new Context([
            'namespace' => $declaringClass->getNamespaceName(),
            'class' => $declaringClass->getShortName(),
            'property' => $reflection->name,
            'filename' => $declaringClass->getFileName(),
        ]));

        /** @var OA\Property $oaProperty */
        if ($reflection instanceof \ReflectionProperty && !$oaProperty = $this->annotationsReader->getPropertyAnnotation($reflection, OA\Property::class)) {
            return;
        } elseif ($reflection instanceof \ReflectionMethod && !$oaProperty = $this->annotationsReader->getMethodAnnotation($reflection, OA\Property::class)) {
            return;
        }
        $this->setContext(null);

        // Read @Model annotations
        $this->modelRegister->__invoke(new Analysis([$oaProperty], Util::createContext()), $serializationGroups);

        if (!$oaProperty->validate()) {
            return;
        }

        $property->mergeProperties($oaProperty);
    }
}
