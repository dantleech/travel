<?php

namespace DTL\Travel\TravelBundle;

use Symfony\Component\Finder\Finder;


class TwigExtension extends \Twig_Extension
{
    private $mediaPath;
    private $webPath;

    public function __construct($mediaPath, $webPath)
    {
        $this->mediaPath = $mediaPath;
        $this->webPath = $webPath;
    }

    public function getFunctions()
    {
        return array(
            'medias_for_date' => new \Twig_Function_Method($this, 'getMediasForDate')
        );
    }

    public function getMediasForDate($date)
    {
        $date = \DateTime::createFromFormat('U', $date);
        $path = $this->mediaPath . '/' . $date->format('Y-m-d');

        if (!file_exists($path)) {
            return array();
        }

        $finder = Finder::create();
        $finder->in($path);

        $urls = array();
        foreach ($finder as $fileInfo) {
            $urls[] = $this->webPath . '/' . $date->format('Y-m-d') . '/' . $fileInfo->getFilename();
        }

        return $urls;
    }

    public function getName()
    {
        return 'travel';
    }
}
