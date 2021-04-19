<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendFounderWelcomeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mail:founder-welcome')
            ->setDescription('Send a mail from founder to welcome the user')
            ->setHelp("Send a mail from founder to welcome the user");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailManager = $this->getContainer()->get('mail.manager');
        $userRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:User');
        $users = $userRepository->findForFounderWelcome();
        $count = 0;
        foreach($users as $user) {
            $mailManager->sendFounderWelcome($user);
            $count++;
        }
        $output->writeln($count." emails send. \n");
    }
}