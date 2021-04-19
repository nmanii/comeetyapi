<?php
namespace AppBundle\Command;

use AppBundle\Entity\User;
use AppBundle\Entity\UserStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUsersStatisticsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('users:update-statistics')
            ->setDescription('Update user statistics')
            ->setHelp("Recalculate users statistics");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventUserRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:EventUser');
        $userStatisticsRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:UserStatistics');
        $userLinkRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:UserLink');
        $eventUsersConfirmedRegistration = $eventUserRepository->findPastConfirmedRegistrationCount();
        $eventUsersOrganisation = $eventUserRepository->findPastEventOrganisationCount();
        $userLinks = $userLinkRepository->getSubscriberCountIndexedByUser();

        $userStatisticsInfo = array();

        foreach($eventUsersConfirmedRegistration as $userId => $info) {
            if(!$userStatistics = $userStatisticsRepository->find($userId)) {
                $userStatistics = $this->getNewUserStatistics($userId);
            }
            $userStatistics->setParticipationCount($info['registrationCount']);
            $userStatisticsInfo[$userId] = $userStatistics;
        }

        foreach($eventUsersOrganisation as $userId => $info) {
            if(!isset($userStatisticsInfo[$userId])) {
                if (!$userStatistics = $userStatisticsRepository->find($userId)) {
                    $userStatistics = $this->getNewUserStatistics($userId);
                }
            } else {
                $userStatistics = $userStatisticsInfo[$userId];
            }
            $userStatistics->setEventOrganisationCount($info['organisationCount']);

            $userStatisticsInfo[$userId] = $userStatistics;
        }

        foreach($userLinks as $userId => $countInfo) {
            if(!isset($userStatisticsInfo[$userId])) {
                if (!$userStatistics = $userStatisticsRepository->find($userId)) {
                    $userStatistics = $this->getNewUserStatistics($userId);
                }
            } else {
                $userStatistics = $userStatisticsInfo[$userId];
            }
            $userStatistics->setSubscriberCount($countInfo['nb']);

            $userStatisticsInfo[$userId] = $userStatistics;
        }

        $manager = $this->getContainer()->get('doctrine')->getManager();
        $count = 0;
        foreach($userStatisticsInfo as $userId =>  $userStatistics) {

            if($userStatistics->getParticipationCount() > 4) {
                $level = User::LEVEL_TRAVELER;
            } elseif($userStatistics->getParticipationCount() > 0) {
                $level = User::LEVEL_TOURIST;
            } else {
                $level = User::LEVEL_NEWCOMER;
            }

            if($userStatistics->getEventOrganisationCount() > 9) {
                $level = User::LEVEL_GUIDE;
            } elseif($userStatistics->getEventOrganisationCount() > 0) {
                $level = User::LEVEL_PATHFINDER;
            }

            $userStatistics->getUser()->setLevel($level);
            $manager->persist($userStatistics);
            $count++;
        }
        $manager->flush();
        $output->writeln($count." users statistics updated. \n");
    }

    private function getNewUserStatistics($userId)
    {
        $userStatistics = new UserStatistics();
        $userStatistics->setUser($this->getContainer()->get('doctrine')->getManager()->getReference('AppBundle:User', $userId));
        return $userStatistics;
    }
}