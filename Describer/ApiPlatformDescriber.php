<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Describer;

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ApiPlatformDescriber extends ExternalDocDescriber
{
    public function __construct(Documentation $documentation, NormalizerInterface $normalizer)
    {
        if (!$normalizer->supportsNormalization($documentation, 'json')) {
            throw new \InvalidArgumentException(sprintf('Argument 2 passed to %s() must implement %s and support normalization of %s. The normalizer provided is an instance of %s.', __METHOD__, NormalizerInterface::class, Documentation::class, $normalizer::class));
        }

        parent::__construct(function () use ($documentation, $normalizer) {
            $documentation = (array) $normalizer->normalize(
                $documentation,
                null,
                [DocumentationNormalizer::SPEC_VERSION => 3]
            );

            unset($documentation['basePath']);

            return $documentation;
        });
    }
}
