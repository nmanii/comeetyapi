<?php
/**
 * Created by PhpStorm.
 * User: manii
 * Date: 29/12/16
 * Time: 15:22
 */

namespace AppBundle\Controller;

use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Delete;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserStatisticsController extends RestController
{
    /**
     * REST action which returns EventStatistics by user id.
     * Method: GET
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets an EventStatistics for a given user id",
     *   output = "AppBundle\Entity\EventStatistics",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the page is not found"
     *   }
     * )
     *
     * @param $id
     * @return mixed
     *
     * @Get("/users/{userId}/statistics", name="get_user_statistics")
     */
    public function getUserStatisticsAction($userId)
    {
        $userStatisticsRepository = $this->getRepository('AppBundle:UserStatistics');
        $userStatistics = NULL;
        try {
            $userStatistics = $userStatisticsRepository->findOneBy(['user' => $userId]);
        } catch (\Exception $exception) {
            $userStatistics = NULL;
        }

        if (!$userStatistics) {
            throw new NotFoundHttpException(sprintf('The resource for user \'%s\' was not found.', $userId));
        }

        $view = $this->view($userStatistics);

        if($this->getUser()->getId() == $userId) {
            $view->getContext()->addGroups(['Default', 'user_private']);
        }

        return $view;
    }
}