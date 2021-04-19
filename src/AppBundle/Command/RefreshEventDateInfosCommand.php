<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshEventDateInfosCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('event:refresh-date-infos')
            ->setDescription('Refresh events date infos')
            ->setHelp("Refresh events date infos");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventService = $this->getContainer()->get('event.service');
        $eventRepository = $this->getContainer()->get('doctrine')->getRepository('AppBundle:Event');
        $events = $eventRepository->findAll();
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $count = 0;
        foreach($events as $event) {
            $eventService->calculateUTCInfos($event);
            $manager->persist($event);
            $manager->flush();
            $count++;
        }
        $output->writeln('Events updated'. "\n");
    }
}