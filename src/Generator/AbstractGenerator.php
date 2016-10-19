<?php

namespace DhMakeComposer\Generator;

use Composer\Package\CompletePackageInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractGenerator
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * AbstractGenerator constructor.
     * @param OutputInterface|null $output
     */
    public function __construct(OutputInterface $output = null)
    {
        $this->setOutput($output);
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    abstract public function generate(CompletePackageInterface $package, $outputDirectory);
}