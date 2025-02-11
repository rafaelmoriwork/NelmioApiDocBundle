<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Functional\Controller;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\BazingaUser;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

#[Route(host: 'api.example.com')]
class BazingaController
{
    /**
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=BazingaUser::class)
     * )
     */
    #[Route(path: '/api/bazinga', methods: ['GET'])]
    public function userAction()
    {
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Success",
     *     @Model(type=BazingaUser::class, groups={"foo"})
     * )
     */
    #[Route(path: '/api/bazinga_foo', methods: ['GET'])]
    public function userGroupAction()
    {
    }
}
