<?php

namespace Nails\Email\Console\Command;

use Nails\Common\Helper\Inflector;
use Nails\Console\Command\Base;
use Nails\Console\Exception\ConsoleException;
use Nails\Email\Constants;
use Nails\Factory;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Test
 *
 * @package Nails\Email\Console\Command
 */
class Test extends Base
{
    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('email:test')
            ->setDescription('Sends a test email')
            ->addArgument('email', InputArgument::REQUIRED, 'The email to send the test to')
            ->addOption('template', 't', InputOption::VALUE_OPTIONAL, 'The email template to use')
            ->addOption('component', 'c', InputOption::VALUE_OPTIONAL, 'The provider of the template')
            ->addOption('data', 'd', InputOption::VALUE_OPTIONAL, 'Data for the template, as JSON');
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the command
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        try {

            $this->banner('Send Test Email');

            if ($oInput->getOption('template')) {
                $sTemplate  = $oInput->getOption('template');
                $sComponent = $oInput->getOption('component');
            } else {
                $sTemplate  = 'EmailTest';
                $sComponent = Constants::MODULE_SLUG;
            }

            if (empty($sTemplate)) {
                throw new InvalidOptionException('Template must be provided');

            } elseif (empty($sComponent)) {
                throw new InvalidOptionException('Template\'s component must be provided');
            }

            $sData = $oInput->getOption('data');
            if (!empty($sData)) {
                $aData = json_decode($sData, JSON_OBJECT_AS_ARRAY);
                if (is_null($aData)) {
                    throw new InvalidOptionException('Provided data was not valid JSON. ' . json_last_error_msg());
                }
            }

            /** @var \Nails\Email\Factory\Email $oEmail */
            $oEmail = Factory::factory($sTemplate, $sComponent);
            $oEmail
                ->to($oInput->getArgument('email'))
                ->data($aData ?? $oEmail->getTestData())
                ->send();

            $oOutput->writeln(sprintf(
                'Test %s sent successfully.',
                Inflector::pluralise(count($oEmail->getGeneratedEmails()), 'email')
            ));
            $oOutput->writeln('');

            foreach ($oEmail->getGeneratedEmails() as $oEmail) {
                $oOutput->writeln(sprintf(
                    '<comment>%s</comment>',
                    $oEmail->data->url->viewOnline
                ));
            }

            $oOutput->writeln('');

        } catch (ConsoleException $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                [$e->getMessage()]
            );
        }

        return self::EXIT_CODE_SUCCESS;
    }
}
