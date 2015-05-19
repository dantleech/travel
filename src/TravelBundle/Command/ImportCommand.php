<?php

namespace DTL\Travel\TravelBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class ImportCommand extends Command
{
    private $mediaDirectory;
    private $filesystem;

    public function configure()
    {
        $this->setName('travel:import');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mediaDirectory = __DIR__ . '/../../../source/assets/media';
        $this->filesystem = new Filesystem();
        $travel2010 = $this->import2010();
        foreach ($travel2010 as $travel) {
            $data[] = $travel;
        }
        $travel2013 = $this->import2013($output);
        foreach ($travel2013 as $travel) {
            $data[] = $travel;
        }

        file_put_contents('entries.json', serialize($data));
    }

    public function import2013($output)
    {
        $dom = new \DOMDocument('1.0');
        $dom->loadXml(file_get_contents(__DIR__ . '/../../../data/voyager.xml'));
        $dom->formatOutput = true;
        $xpath = new \DOMXPath($dom);
        $entries = array();

        foreach ($xpath->query('//sv:node[@sv:name="chronology"]/sv:node') as $node) {
            if (!strtotime($node->getAttribute('sv:name'))) {
                continue;
            }

            $date = $node->getAttribute('sv:name');
            $journey = new Journey();
            $journey->date = new \DateTime($date);

            $entries[] = $journey;

            foreach ($xpath->query('./sv:property[@sv:name="references"]/sv:value', $node) as $reference) {
                foreach ($xpath->query('//sv:node/sv:property[@sv:name="jcr:uuid"]/sv:value[text() = "' . $reference->nodeValue .'"]') as $referenced) {
                    $phpcrClass = null;
                    $node = $referenced->parentNode->parentNode;
                    foreach ($node->childNodes as $child) {
                        if ($child->getAttribute('sv:name') == 'phpcr:class') {
                            $phpcrClass = $child->nodeValue;
                        }
                    }

                    if (null === $phpcrClass) {
                        continue;
                    }

                    $output->writeln('Importing: ' . $node->getAttribute('sv:name'));
                    switch ($phpcrClass) {
                        case 'Sandbox\MediaBundle\Document\Media':
                            $this->import2013Media($node->getAttribute('sv:name'), $journey->date);
                            break;
                        case 'DTL\TravelBundle\Document\VoyagePost':
                            $this->import2013Post($journey, $xpath, $node);
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        return $entries;
    }

    private function import2013Post($journey, $xpath, $node)
    {
        foreach ($xpath->query('./sv:property', $node) as $node) {
            $value = $node->firstChild->nodeValue;
            switch ($node->getAttribute('sv:name')) {
                case 'title':
                    $journey->title = $value;
                    break;
                case 'body':
                    $journey->blog = $value;
                    break;
                case 'maxSpeed':
                    $journey->maxSpeed = $value;
                    break;
                case 'distance':
                    $journey->distance= $value;
                    break;
                case 'duration':
                    $journey->duration = $value;
                    break;
                default:
            }
        }
    }

    private function import2013Media($name, $date)
    {
        $path = __DIR__ . '/../../../travel-blog/web/uploads/media/default/cms/media/' . $name;

        if (!file_exists($path)) {
            return;
        }
        $dir = $this->mediaDirectory . '/' . $date->format('Y-m-d');

        if (!file_exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        if (file_exists($dir .'/' . $name)) {
            return;
        }

        copy($path, $dir . '/' . $name);
    }

    public function import2010()
    {
        $handle = fopen(__DIR__ . '/../../../data/travel.2010.csv', 'r');
        $entries = array();

        while ($line = fgetcsv($handle)) {
            array_shift($line); // stage title
            array_shift($line); // stage description

            $journey = new Journey;
            $journey->date = new \DateTime(array_shift($line));
            $journey->title = array_shift($line);
            $journey->maxSpeed = array_shift($line) * 1.609344 * 1000;
            $journey->distance = array_shift($line) * 1.609344;
            $journey->duration = array_shift($line);
            $journey->blog = array_shift($line);

            $entries[] = $journey;
        }

        // copy images
        $finder = new Finder();
        $finder->in(__DIR__ . '/../../../travel2010/web/media');
        $finder->name('*.jpg');
        foreach ($finder as $file) {
            preg_match('{(20[0-9]{2})([0-9]{2})([0-9]{2})_(.*)}', $file->getFilename(), $matches);
            if (!$matches) {
                continue;
            }

            $path = $this->mediaDirectory . '/' . $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            if (!file_exists($path)) {
                $this->filesystem->mkdir($path);
            }

            copy($file->getRealPath(), $path . '/' . $matches[4]);
        }

        return $entries;
    }
}

class Journey
{
    public $date;
    public $title;
    public $maxSpeed;
    public $distance;
    public $duration;
    public $blog;
}
