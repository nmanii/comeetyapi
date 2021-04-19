<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendEventReminderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mail:event-reminder')
            ->setDescription('Send a mail to remind user that they are register to an event')
            ->setHelp("Send a mail to remind user that they are register to an event");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailManager = $this->getContainer()->get('mail.manager');
        $eventUserRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:EventUser');
        $eventUsers = $eventUserRepository->findForEventNextDay();


        $manager = $this->getContainer()->get('doctrine')->getManager();
        $count = 0;
        foreach($eventUsers as $eventUser) {
            //If is organiser
            if($eventUser->getUser() === $eventUser->getEvent()->getUser()) {
                $mailManager->sendEventReminderOrganiser1Day($eventUser->getUser(), $eventUser->getEvent());
            } else {
                $mailManager->sendEventReminder1Day($eventUser->getUser(), $eventUser->getEvent());
            }
            $count++;
        }
        $output->writeln($count." emails send. \n");
    }
}