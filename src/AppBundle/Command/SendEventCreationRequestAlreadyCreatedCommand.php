<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendEventCreationRequestAlreadyCreatedCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mail:event-creation-request-already-created')
            ->setDescription('Send a mail to all user who have not created enough event but have created in the past')
            ->setHelp("Send a mail to all user who have not created enough event but have created in the past");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailManager = $this->getContainer()->get('mail.manager');
        $eventUserRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:EventUser');
        $eventUsersConfirmedRegistration = $eventUserRepository->findUsersWithMinimumConfirmedRegistrationSinceLastEventCreationAndNoRequestSent(3);
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $count = 0;
        foreach($eventUsersConfirmedRegistration as $key => $info) {
            $mailManager->sendEventCreationUserAlreadyCreatedRequest($info[0], $info['registrationCount']);
            $count++;
        }
        $output->writeln($count." emails send. \n");
    }
}