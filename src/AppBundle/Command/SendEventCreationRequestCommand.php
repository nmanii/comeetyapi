<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendEventCreationRequestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mail:event-creation-request')
            ->setDescription('Send a mail to all user who have not create enough event')
            ->setHelp("Send a mail to all user who have not create enough event");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailManager = $this->getContainer()->get('mail.manager');
        $eventUserRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:EventUser');
        $eventUsersConfirmedRegistration = $eventUserRepository->findUsersWithMinimumConfirmedRegistrationButNoCreationAndNoRequestSent(3);
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $count = 0;
        foreach($eventUsersConfirmedRegistration as $userId => $info) {
            $mailManager->sendEventCreationUserNeverCreatedRequest($info[0], $info['registrationCount']);
            $count++;
        }
        $output->writeln($count." emails send. \n");
    }
}