<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Create\NodesCommand.
 */

namespace Drupal\Console\Command\Create;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class NodesCommand
 * @package Drupal\Console\Command\Generate
 */
class NodesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:nodes')
            ->setDescription($this->trans('commands.create.nodes.description'))
            ->addArgument(
                'content-types',
                InputArgument::IS_ARRAY,
                $this->trans('commands.create.nodes.arguments.content-types')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.arguments.limit')
            )
            ->addOption(
                'title-words',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.arguments.title-words')
            )
            ->addOption(
                'time-range',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.nodes.arguments.time-range')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // --content type argument
        $contentTypes = $input->getArgument('content-types');
        if (!$contentTypes) {
            $bundles = $this->getDrupalApi()->getBundles();
            $contentTypes = $io->choice(
                $this->trans('commands.create.nodes.questions.content-type'),
                array_values($bundles),
                null,
                true
            );

            $contentTypes = array_map(
                function ($contentType) use ($bundles) {
                    return array_search($contentType, $bundles);
                },
                $contentTypes
            );

            $input->setArgument('content-types', $contentTypes);
        }

        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $io->ask(
                $this->trans('commands.create.nodes.questions.limit'),
                10
            );
            $input->setOption('limit', $limit);
        }

        $titleWordsMin = $input->getOption('title-words');
        if (!$titleWordsMin) {
            $titleWordsMin = $io->ask(
                $this->trans('commands.create.nodes.questions.title-words'),
                5
            );

            $input->setOption('title-words', $titleWordsMin);
        }

        $timeRange = $input->getOption('time-range');
        if (!$timeRange) {
            $timeRanges = $this->getTimeRange();

            $timeRange = $io->choice(
                $this->trans('commands.create.nodes.questions.time-range'),
                array_values($timeRanges)
            );

            $input->setOption('time-range',  array_search($timeRange, $timeRanges));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $createNodes = $this->getDrupalApi()->getCreateNodes();

        $contentTypes = $input->getArgument('content-types');
        $limit = $input->getOption('limit')?:10;
        $titleWords = $input->getOption('title-words')?:5;
        $timeRange = $input->getOption('time-range')?:'N';

        $nodes = $createNodes->createNode(
            $contentTypes,
            $limit,
            $titleWords,
            $timeRange
        );

        $tableHeader = [
          $this->trans('commands.create.nodes.messages.node-id'),
          $this->trans('commands.create.nodes.messages.content-type'),
          $this->trans('commands.create.nodes.messages.title'),
          $this->trans('commands.create.nodes.messages.created'),
        ];

        $io->table($tableHeader, $nodes['success']);

        $io->success(
            sprintf(
                $this->trans('commands.create.nodes.messages.generated-content'),
                $limit
            )
        );

        return;
    }

    /**
     * @return array
     */
    private function getTimeRange()
    {
        $timeRanges = [
            1 => sprintf('N | %s', $this->trans('commands.create.nodes.questions.time-ranges.0')),
            3600 => sprintf('H | %s', $this->trans('commands.create.nodes.questions.time-ranges.1')),
            86400 => sprintf('D | %s', $this->trans('commands.create.nodes.questions.time-ranges.2')),
            604800 => sprintf('W | %s', $this->trans('commands.create.nodes.questions.time-ranges.3')),
            2592000 => sprintf('M | %s', $this->trans('commands.create.nodes.questions.time-ranges.4')),
            31536000 => sprintf('Y | %s', $this->trans('commands.create.nodes.questions.time-ranges.5'))
        ];

        return $timeRanges;
    }
}