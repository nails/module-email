<?php

namespace Nails\Email\Console\Command;

use Nails\Console\Command\Base;
use Nails\Console\Exception\ConsoleException;
use Nails\Email\Exception\EmailerException;
use Nails\Email\Service\Emailer;
use Nails\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->addArgument('email', InputArgument::REQUIRED, 'The email to send the test to');
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

            /** @var Emailer $oEmailer */
            $oEmailer = Factory::service('Emailer', 'nails/module-email');
            $bResult  = $oEmailer->send((object) [
                'type'     => 'test_email',
                'to_email' => $oInput->getArgument('email'),
                'data'     => [
                    'sentAt' => Factory::factory('DateTime')->format('Y-m-d H:i:s'),
                ],
            ]);

            if (!$bResult) {
                throw new EmailerException(
                    'Failed to send email: ' . $oEmailer->lastError()
                );
            }

            $oOutput->writeln('Test email sent successfully.');
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
