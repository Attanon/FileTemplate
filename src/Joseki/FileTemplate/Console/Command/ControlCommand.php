<?php

namespace Joseki\FileTemplate\Console\Command;

use Joseki\FileTemplate\InvalidArgumentException;
use Joseki\FileTemplate\Schema;
use Joseki\Utils\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ControlCommand extends Command
{
    /** @var Schema[] */
    private $schemaList;

    private $selectedSchema;

    private $selectedDir;

    /** @var */
    private $rootDir;



    /**
     * ControlCommand constructor.
     * @param array $schemaList
     */
    public function __construct(array $schemaList, $rootDir)
    {
        parent::__construct();
        $this->schemaList = $schemaList;
        $this->rootDir = rtrim($rootDir, '/\\ ');
    }



    /**
     * @return \Joseki\FileTemplate\Schema[]
     */
    public function getSchemaList()
    {
        return $this->schemaList;
    }



    protected function configure()
    {
        $this->setName('joseki:file-templates');
        $this->setDescription('FileTemplate generator');

        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Which command (set of templates) are you going to create?'
        );

        $this->addOption(
            'dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify relative directory'
        );
    }



    protected function interact(InputInterface $input, OutputInterface $output)
    {

        $this->selectedSchema = $input->getArgument('name');

        $helper = $this->getHelper('question');

        if (!array_key_exists($this->selectedSchema, $this->schemaList)) {
            throw new InvalidArgumentException("FileTemplate name '{$this->selectedSchema}' not found.");
        }

        $schema = $this->schemaList[$this->selectedSchema];
        foreach ($schema->getUndefinedVariables() as $var) {
            $question = new Question("Please enter value for variable $var:");
            $answer = $helper->ask($input, $output, $question);
            $schema->setVariable($var, $answer);
        }

        $dir = $input->getOption('dir');
        if (!$dir) {
            $dir = str_replace('\\', '/', $schema->getVariable('NAMESPACE'));
        }
        $this->selectedDir = trim($dir, '/\\');

    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schema = $this->schemaList[$this->selectedSchema];
        $dir = $this->rootDir . '/' . $this->selectedDir;
        foreach ($schema->getFiles() as $var => $templatePath) {
            $fileName = $dir . '/' . $schema->getVariable($var);
            @mkdir(dirname($fileName), 0777, true);
            $content = $schema->translate(file_get_contents($templatePath));
            file_put_contents($fileName, $content);
            $output->writeln('Created file: ' . FileSystem::normalizePath($fileName));
        }
    }
}
