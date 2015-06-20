<?php

namespace DTL\Travel\TravelBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TravelExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $def = $container->register('import_command', 'DTL\Travel\TravelBundle\Command\ImportCommand');
        $def->addTag('console.command');

        $def = $container->register('twig_extension', 'DTL\Travel\TravelBundle\TwigExtension');
        $def->addArgument(__DIR__ . '/../../../source/assets/media');
        $def->addArgument('/assets/media');
        $def->addTag('twig.extension');
    }
}
