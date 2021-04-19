<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendNoShowReportedMailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mail:no-show-reported-mail')
            ->setDescription('Send a mail to all user who were noshowed')
            ->setHelp("Send a mail to all user who were noshowed");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailManager = $this->getContainer()->get('mail.manager');
        $eventUserRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:UserFeedback');
        $mailLogRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:MailLog');
        $userRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User');
        $usersFeedbacks = $eventUserRepository->findUsersLastNoShow();
        $count = 0;
        foreach($usersFeedbacks as $usersFeedback) {
            $mailLog = $mailLogRepository->findOneBy(['user'=>$usersFeedback->getReference(), 'mailName'=>'noShowReportedWarning']);
            if(empty($mailLog)) {
                $reportedUser = $userRepository->findOneById($usersFeedback->getReference());
                if(!empty($reportedUser)) {
                    $mailManager->sendNoShowReportedWarning($reportedUser, $usersFeedback);
                }
                $count++;
            }
        }
        $output->writeln($count." emails send. \n");
    }
}