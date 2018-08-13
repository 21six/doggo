<?php

namespace TwentyOneSix\Doggo;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Doggo extends Command
{
    protected $input;
    protected $output;

    protected $help = '';

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('install:' . $this->command)

            // the short description shown while running "php bin/console list"
            ->setDescription($this->description)

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp($this->help)
        ;

        if (method_exists($this, 'params')) {
            $this->params();
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->currentDirectory = getcwd() ;
        if (file_exists('.test')) {
            $this->currentDirectory .= '/test';
        }
        $this->handle();
    }

    abstract protected function handle();

    protected function header(string $heading)
    {
        $headingLength = strlen($heading) + 1;
        $headingLine = '';
        for ($i=0; $i<$headingLength; $i++) {
            $headingLine .= '=';
        }
        $this->output->writeln([
            '',
            $heading,
            $headingLine,
            '',
        ]);
    }

    protected function write($text)
    {
        $this->output->writeln($text);
    }

    protected function writeInfo(string $text)
    {
        $this->write('<info>' . $text . '</info>');
    }

    protected function writeComment(string $text)
    {
        $this->write('<comment>' . $text . '</comment>');
    }

    protected function writeError(string $text)
    {
        $this->write('<error>' . $text . '</error>');
    }

    protected function gitClone(string $gitRepo, string $path)
    {
        $this->runCommands([
            'git clone ' . escapeshellarg($gitRepo) . ' ' . escapeshellarg($path),
            'rm -rf ' . escapeshellarg($path . '/.git'),
        ]);
    }

    protected function runCommand(string $command)
    {
        // print when in debug mode -vvv
        $this->output->writeln('<comment>' . $command . '</comment>', OutputInterface::VERBOSITY_DEBUG);
        $process = new Process($command);
        return $process->mustRun();
    }

    protected function runCommands(array $commands, callable $callback = null)
    {
        foreach ($commands as $command) {
            $this->runCommand($command, $callback);
        }
    }

    protected function delete(string $path)
    {
        $this->runCommand('rm -rf ' . $path);
    }

    protected function deletes(array $paths)
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }

    protected function installComposerDependencies(string $path)
    {
        $this->output->writeln('<info>Installing Composer Dependencies</info>');
        $commands = [
            'cd ' . escapeshellarg($path),
            'composer install',
        ];
        $this->runCommands($commands);
    }
}
