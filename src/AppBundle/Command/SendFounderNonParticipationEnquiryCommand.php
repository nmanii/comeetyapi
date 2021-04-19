<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendFounderNonParticipationEnquiryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mail:founder-non-participation-enquiry')
            ->setDescription('Send a mail from founder to know why the user did not join any event')
            ->setHelp('Send a mail from founder to know why the user did not join any event');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailManager = $this->getContainer()->get('mail.manager');
        $userRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User');
        $users = $userRepository->findForFounderNonParticipationEnquiry();

        $count = 0;
        foreach($users as $user) {
            $mailManager->sendFounderNonParticipationEnquiry($user);
            $count++;
        }
        $output->writeln($count." emails send. \n");
    }
}