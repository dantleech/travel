<?php

namespace DTL\Travel\TravelBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    private $journeyPath;
    public function configure()
    {
        $this->setName('travel:generate');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->journeyPath = __DIR__ . '/../../../source/_journies';
        $journies = unserialize(file_get_contents('journies'));

        foreach ($journies as $journey) {
            $template = $this->generateJourney($journey);
            $filename = $this->journeyPath . '/' . $journey->date->format('Y-m-d') . '.md';
            file_put_contents($filename, $template);
        }
    }

    private function generateJourney($journey)
    {
        $template = array();
        $template[] = '---';
        $template[] = 'layout: journey';
        $template[] = 'date: ' . $journey->date->format('Y-m-d');
        $template[] = 'title: ' . ($journey->title ? : $journey->date->format('l jS F, o'));
        $template[] = 'hasJournal: ' . ($journey->blog ? 'yes' : 'no');
        if ($journey->maxSpeed) {
            $template[] = 'maxSpeed: ' . $journey->maxSpeed;
        }
        if ($journey->distance) {
            $template[] = 'distance: ' . $journey->distance;
        }
        if ($journey->duration) {
            $template[] = 'duration: ' . $journey->duration;
        }
        $template[] = '---';
        $template[] = $journey->blog ? : 'No journal entry';

        return implode(PHP_EOL, $template);
    }
}
