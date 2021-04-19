<?php
namespace AppBundle\Command;

use AppBundle\Entity\User;
use AppBundle\Entity\UserStatistics;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUsersCommitmentScoreCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('users:update-commitment-score')
            ->setDescription('Update user commitment score')
            ->setHelp("Recalculate users commitment score");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventUserRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:EventUser');
        $userStatisticsRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:UserStatistics');
        $userRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User');
        $users = $userRepository->findBy(['confirmed' => 1]);


        $manager = $this->getContainer()->get('doctrine')->getManager();
        foreach($users as $user) {
            //Start score is 3
            $score = 3;
            $userId = $user->getId();

            if (!$userStatistics = $userStatisticsRepository->find($userId)) {
                $userStatistics = $this->getNewUserStatistics($userId);
            }
            $userCommitmentData = $eventUserRepository->findPastCommitmentDataByUserId($userId);
            foreach ($userCommitmentData as  $info) {
                if($info['role'] === 'creator') {
                    if($info['rating'] === 'noshow') {
                        $score = $this->addScore($score, -3);
                    } else {
                        $score = $this->addScore($score, 2);
                    }
                } elseif($info['rating'] === 'noshow') {
                    $score = $this->addScore($score, -2);
                } elseif($info['state'] === 'cancelled') {
                    $score = $this->addScore($score, -1);
                } elseif($info['state'] === 'confirmed') {
                    $score = $this->addScore($score, 1);
                }
            }

            $userStatistics->setCommitmentScore($score);

            $manager->persist($userStatistics);
            $manager->flush();
        }

        $output->writeln("Users commitment score updated. \n");
    }

    private function addScore($baseScore, $scoreToAdd) {
        $score = $baseScore + $scoreToAdd;

        if($score > 5) {
            $score = 5;
        }
        if($score < 0) {
            $score = 0;
        }
        return $score;
    }

    private function getNewUserStatistics($userId)
    {
        $userStatistics = new UserStatistics();
        $userStatistics->setUser($this->getContainer()->get('doctrine')->getManager()->getReference('AppBundle:User', $userId));
        return $userStatistics;
    }
}