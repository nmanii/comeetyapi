<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendZeroTrustScoreMailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mail:zero-trust-score-mail')
            ->setDescription('Send a mail to all user who reached a zero trust score')
            ->setHelp("Send a mail to all user who reached a zero trust score");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailManager = $this->getContainer()->get('mail.manager');
        $eventUserRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:UserStatistics');
        $mailLogRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:MailLog');
        $usersStatistics = $eventUserRepository->findByCommitmentScore(0);
        $count = 0;
        foreach($usersStatistics as $userStatistics) {
            $mailLog = $mailLogRepository->findOneBy(['user'=>$userStatistics->getUser()->getId(), 'mailName'=>'zeroTrustScoreMail']);
            if(empty($mailLog)) {
                $mailManager->sendZeroTrustScoreReachedMail($userStatistics->getUser());
                $count++;
            }
        }
        $output->writeln($count." emails send. \n");
    }
}