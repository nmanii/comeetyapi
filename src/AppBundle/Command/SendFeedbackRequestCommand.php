<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendFeedbackRequestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mail:feedback-request')
            ->setDescription('Send a mail to all user who have participated to an event')
            ->setHelp("Send a mail to all user who have participated to an event");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailManager = $this->getContainer()->get('mail.manager');
        $eventUserRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:EventUser');
        $eventUsers = $eventUserRepository->findConfirmedUsersWithFeedbackRequestPending();
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $count = 0;

        foreach ($eventUsers as $eventUser) {
            if ($eventUser->getEvent()->getUser() === $eventUser->getUser()) {
                $mailManager->sendFeedbackRequestToOrganizer($eventUser->getEvent(), $eventUser->getUser());
            } else {
                $mailManager->sendFeedbackRequestToParticipant($eventUser->getEvent(), $eventUser->getUser());
            }
            $eventUser->setFeedbackRequested(true);
            $manager->persist($eventUser);
            $manager->flush();
            $count++;
        }

        $output->writeln($count." emails send. \n");
    }
}