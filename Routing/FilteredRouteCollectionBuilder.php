<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Routing;

use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Nelmio\ApiDocBundle\Util\ControllerReflector;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class FilteredRouteCollectionBuilder
{
    public function __construct(
        private Reader $annotationReader,
        private ControllerReflector $controllerReflector,
        private string $area,
        private array $options = []
    ) {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                'path_patterns' => [],
                'host_patterns' => [],
                'name_patterns' => [],
                'with_annotation' => false,
                'disable_default_routes' => false,
            ])
            ->setAllowedTypes('path_patterns', 'string[]')
            ->setAllowedTypes('host_patterns', 'string[]')
            ->setAllowedTypes('name_patterns', 'string[]')
            ->setAllowedTypes('with_annotation', 'boolean')
            ->setAllowedTypes('disable_default_routes', 'boolean')
        ;

        if (array_key_exists(0, $options)) {
            @trigger_error(sprintf('Passing an indexed array with a collection of path patterns as argument 1 for `%s()` is deprecated since 3.2.0, expected structure is an array containing parameterized options.', __METHOD__), E_USER_DEPRECATED);

            $normalizedOptions = ['path_patterns' => $options];
            $options = $normalizedOptions;
        }
        $this->options = $resolver->resolve($options);
    }

    public function filter(RouteCollection $routes): RouteCollection
    {
        $filteredRoutes = new RouteCollection();
        foreach ($routes->all() as $name => $route) {
            if ($this->matchPath($route)
                && $this->matchHost($route)
                && $this->matchAnnotation($route)
                && $this->matchName($name)
                && $this->defaultRouteDisabled($route)
            ) {
                $filteredRoutes->add($name, $route);
            }
        }

        return $filteredRoutes;
    }

    private function matchPath(Route $route): bool
    {
        foreach ($this->options['path_patterns'] as $pathPattern) {
            if (preg_match('{'.$pathPattern.'}', $route->getPath())) {
                return true;
            }
        }

        return 0 === count($this->options['path_patterns']);
    }

    private function matchHost(Route $route): bool
    {
        foreach ($this->options['host_patterns'] as $hostPattern) {
            if (preg_match('{'.$hostPattern.'}', $route->getHost())) {
                return true;
            }
        }

        return 0 === count($this->options['host_patterns']);
    }

    private function matchName(string $name): bool
    {
        foreach ($this->options['name_patterns'] as $namePattern) {
            if (preg_match('{'.$namePattern.'}', $name)) {
                return true;
            }
        }

        return 0 === count($this->options['name_patterns']);
    }

    private function matchAnnotation(Route $route): bool
    {
        if (false === $this->options['with_annotation']) {
            return true;
        }

        $reflectionMethod = $this->controllerReflector->getReflectionMethod($route->getDefault('_controller'));

        if (null === $reflectionMethod) {
            return false;
        }

        /** @var Areas|null $areas */
        $areas = $this->annotationReader->getMethodAnnotation(
            $reflectionMethod,
            Areas::class
        );

        if (null === $areas) {
            $areas = $this->annotationReader->getClassAnnotation($reflectionMethod->getDeclaringClass(), Areas::class);
        }

        return (null !== $areas) ? $areas->has($this->area) : false;
    }

    private function defaultRouteDisabled(Route $route): bool
    {
        if (false === $this->options['disable_default_routes']) {
            return true;
        }

        $method = $this->controllerReflector->getReflectionMethod(
            $route->getDefault('_controller') ?? ''
        );

        if (null === $method) {
            return false;
        }

        $annotations = $this->annotationReader->getMethodAnnotations($method);

        foreach ($annotations as $annotation) {
            if (str_contains($annotation::class, 'Nelmio\\ApiDocBundle\\Annotation')
                || str_contains($annotation::class, 'OpenApi\\Annotations')
            ) {
                return true;
            }
        }

        return false;
    }
}
