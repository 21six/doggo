<?php

namespace TwentyOneSix\Ordu\Installer;

use TwentyOneSix\Ordu\Ordu;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class WordPress extends Ordu
{
    protected $command = 'wordpress';
    protected $description = 'Creates a new WordPress site.';
    protected $help = 'This command allows you to create a new WordPress site.';

    protected $defaultProjectName = 'new-wordpress';

    protected function params()
    {
        $this->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'The name of the folder to create (defaults to `' . $this->defaultProjectName . '`)'
        );

        $this->addOption(
            '--no-lumberjack',
            null,
            InputOption::VALUE_NONE,
            'Will not install theme `lumberjack`'
        );
    }

    protected function handle()
    {
        $this->header('Installing WordPress');

        $projectName = $this->input->getArgument('name') ?? $this->defaultProjectName;

        $this->writeInfo('Project Name: ' . $projectName);

        $rootPath = $this->currentDirectory . '/' . $projectName;
        $projectPath = $this->currentDirectory . '/' . $projectName;
        $this->delete($projectPath);

        if (file_exists($projectPath)) {
            $this->output->writeln('<error>Can\'t install to: ' . $projectPath . '. The directory already exists</error>');
            return;
        }

        $this->install($rootPath, $projectPath);
        $this->writeInfo('Done!');
    }

    protected function install(string $rootPath, string $projectPath)
    {
        $this->gitClone('git@github.com:wolfiezero/wordpress.git', $projectPath);
        $this->installComposerDependencies($projectPath);

        if (! $this->input->getOption('no-lumberjack')) {
            $this->gitClone('git@github.com:rareloop/lumberjack.git', $projectPath . '/public/themes/lumberjack');

            // Write deleting files
            $this->writeInfo('Deleting unrequired files and folders');
            $this->deletes([
                $projectPath . '/public/themes/lumberjack/CHANGELOG.md',
                $projectPath . '/public/themes/lumberjack/.github',
                $projectPath . '/public/themes/lumberjack/assets',
                $projectPath . '/public/themes/lumberjack/.git',
                $projectPath . '/LICENSE.md',
            ]);
        }
    }
}
