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

    protected $rootPath = '';

    protected $timeout = 300;

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

    protected function gitClone(string $gitRepo, string $path = '')
    {
        if (! $path) {
            $path = $this->rootPath;
        }

        $this->runCommands([
            'git clone --depth=1 ' . escapeshellarg($gitRepo) . ' ' . escapeshellarg($path),
            'rm -rf ' . escapeshellarg($path . '/.git'),
        ]);
    }

    protected function runCommand(string $command)
    {
        $this->output->writeln('<comment>' . $command . '</comment>', OutputInterface::VERBOSITY_DEBUG);

        $process = new Process($command);
        $process->setTimeout($this->timeout);

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

    protected function composerInstallDependencies(string $path = '')
    {
        if (! $path) {
            $path = $this->rootPath;
        }
        $this->output->writeln('<info>Installing Composer Dependencies</info>');
        $this->runCommand(
            'cd ' . escapeshellarg($path) . ' && ' .
            'composer install'
        );
    }

    protected function composerRequirePackages(array $packages, string $path = '')
    {
        if (! $path) {
            $path = $this->rootPath;
        }
        foreach ($packages as $package) {
            $this->composerRequirePackage($package, $path);
        }
    }

    protected function composerRequirePackage(string $package, string $path = '')
    {
        if (! $path) {
            $path = $this->rootPath;
        }
        $this->runCommand(
            'cd ' . escapeshellarg($path) . ' && ' .
            ' composer require ' . $package
        );
    }

    protected function composerRunScript(string $scriptName, string $path = '')
    {
        if (! $path) {
            $path = $this->rootPath;
        }
        $this->runCommand(
            'cd ' . escapeshellarg($path) . ' && ' .
            'composer ' . $scriptName
        );
    }

    protected function makeFileFromStub(string $fileName, string $createPath, array $replacements = [])
    {
        $stub = file_get_contents(__DIR__ . '/stubs/' . $fileName);

        foreach ($replacements as $find => $replace) {
            $stub = str_replace($find, $replace, $stub);
        }

        $directory = dirname($createPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0754, true);
        }

        file_put_contents($createPath, $stub);
    }

    protected function updateFileContents(string $filePathAndName, array $replacements)
    {
        $contents = file_get_contents($filePathAndName);

        foreach ($replacements as $find => $replace) {
            $contents = str_replace($find, $replace, $contents);
        }

        file_put_contents($filePathAndName, $contents);
    }

    protected function makeRandomString(int $length = 64): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
        $size = strlen($characters);
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[rand(0, $size - 1)];
        }

        return $key;
    }
}
