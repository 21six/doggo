<?php

namespace TwentyOneSix\Doggo\Installer;

use TwentyOneSix\Doggo\Doggo;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class WordPress extends Doggo
{
    protected $command = 'wordpress';
    protected $description = 'Creates a new WordPress site.';
    protected $help = 'This command allows you to create a new WordPress site.';

    protected $projectName = 'new-wordpress';

    protected $projectPath = '';

    protected function params()
    {
        $this->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'The name of the folder to create (defaults to `' . $this->projectName . '`)'
        );

        $this->addOption(
            '--no-lumberjack',
            null,
            InputOption::VALUE_NONE,
            'Will not install theme `lumberjack`'
        );

        $this->addOption(
            '--no-frontend-build',
            null,
            InputOption::VALUE_NONE,
            'Will not install front-end build system'
        );
    }

    protected function handle()
    {
        $this->header('Installing WordPress');

        $this->projectName = $this->input->getArgument('name') ?? $this->projectName;

        $this->writeInfo('Project Name: ' . $this->projectName);

        $this->rootPath = $this->currentDirectory . '/' . $this->projectName;
        $this->projectPath = $this->currentDirectory . '/' . $this->projectName;

        $this->delete($this->projectPath);

        if (file_exists($this->projectPath)) {
            $this->output->writeln('<error>Can\'t install to: ' . $this->projectName . '. The directory already exists</error>');
            return;
        }

        $this->install();
        $this->writeInfo('Done!');
        $this->writeComment('All you need to do is update the .env file');
    }

    protected function install()
    {
        $this->gitClone('git@github.com:21six/wordpress.git');
        $this->composerInstallDependencies();
        $this->composerRunScript('new-project');

        $this->makeFileFromStub(
            '.htaccess.stub',
            $this->projectPath . '/.htaccess'
        );

        $this->updateFileContents(
            $this->projectPath . '/.env',
            [
                'AUTH_KEY=\'\'' => 'AUTH_KEY=\'' . $this->makeRandomString() . '\'',
                'SECURE_AUTH_KEY=\'\'' => 'SECURE_AUTH_KEY=\'' . $this->makeRandomString() . '\'',
                'LOGGED_IN_KEY=\'\'' => 'LOGGED_IN_KEY=\'' . $this->makeRandomString() . '\'',
                'NONCE_KEY=\'\'' => 'NONCE_KEY=\'' . $this->makeRandomString() . '\'',
                'AUTH_SALT=\'\'' => 'AUTH_SALT=\'' . $this->makeRandomString() . '\'',
                'SECURE_AUTH_SALT=\'\'' => 'SECURE_AUTH_SALT=\'' . $this->makeRandomString() . '\'',
                'LOGGED_IN_SALT=\'\'' => 'LOGGED_IN_SALT=\'' . $this->makeRandomString() . '\'',
                'NONCE_SALT=\'\'' => 'NONCE_SALT=\'' . $this->makeRandomString() . '\'',
            ]
        );

        if (! $this->input->getOption('no-lumberjack')) {
            $this->deleteDefaultTheme();
            $this->lumberjack();
        }

        if (! $this->input->getOption('no-frontend-build')) {
            $this->webpackMix();
        }
    }

    protected function deleteDefaultTheme()
    {
        $this->delete($this->projectPath . '/public/themes/app');
    }

    protected function lumberjack()
    {
        $this->writeInfo('Setting up Lumberjack - https://docs.lumberjack.rareloop.com/');

        $this->composerRequirePackage('rareloop/lumberjack-core');
        $this->gitClone(
            'git@github.com:rareloop/lumberjack.git',
            $this->projectPath . '/public/themes/' . $this->projectName
        );

        // Write deleting files
        $this->writeInfo('Deleting unrequired files and folders');
        $this->deletes([
            $this->projectPath . '/public/themes/' . $this->projectName . '/CHANGELOG.md',
            $this->projectPath . '/public/themes/' . $this->projectName . '/style.css',
            $this->projectPath . '/public/themes/' . $this->projectName . '/.github',
            $this->projectPath . '/public/themes/' . $this->projectName . '/assets',
            $this->projectPath . '/public/themes/' . $this->projectName . '/.git',
        ]);

        $this->makeFileFromStub(
            'style.css.stub',
            $this->projectPath . '/public/themes/' . $this->projectName . '/style.css',
            [
                'Theme_Name' => $this->projectName
            ]
        );

        $this->updateFileContents(
            $this->projectPath . '/.env',
            [
                'WP_THEME=app' => 'WP_THEME=' . $this->projectName,
            ]
        );
    }

    protected function webpackMix()
    {
        $this->writeInfo('Setting up Webpack Mix - https://laravel.com/docs/master/mix');

        mkdir($this->projectPath . '/public/assets');
        mkdir($this->projectPath . '/public/assets/js');
        mkdir($this->projectPath . '/public/assets/css');

        mkdir($this->projectPath . '/public/themes/' . $this->projectName . '/assets');
        mkdir($this->projectPath . '/public/themes/' . $this->projectName . '/assets/js');
        mkdir($this->projectPath . '/public/themes/' . $this->projectName . '/assets/sass');
        file_put_contents($this->projectPath . '/public/themes/' . $this->projectName . '/assets/js/app.js', '');
        file_put_contents($this->projectPath . '/public/themes/' . $this->projectName . '/assets/sass/app.scss', '');

        $this->makeFileFromStub(
            'package.json.stub',
            $this->projectPath . '/package.json'
        );

        $this->makeFileFromStub(
            'webpack.mix.js.stub',
            $this->projectPath . '/webpack.mix.js',
            [
                '/app/' => '/' . $this->projectName . '/'
            ]
        );

        $this->writeInfo('Installing node modules');
        $this->runCommand('cd ' . $this->projectPath . ' && npm install');

        $this->writeInfo('Running first build of front-end');
        $this->runCommand('cd ' . $this->projectPath . ' && npm run build');
    }
}
