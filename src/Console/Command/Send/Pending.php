<?php

namespace Nails\Email\Console\Command\Send;

use Nails\Common\Helper\Inflector;
use Nails\Common\Helper\Model\Paginate;
use Nails\Common\Helper\Model\Select;
use Nails\Common\Helper\Model\Where;
use Nails\Config;
use Nails\Console\Command\Base;
use Nails\Console\Exception\ConsoleException;
use Nails\Email\Constants;
use Nails\Email\Exception\EmailerException;
use Nails\Email\Model\Email;
use Nails\Factory;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Pending
 *
 * @package Nails\Email\Console\Command\Send
 */
class Pending extends Base
{
    const PER_PROCESS = 500;

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('email:send:pending')
            ->setDescription('Sends pending emails');
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

            $this->banner('Send pending email');

            /** @var \Nails\Email\Model\Email $oModel */
            $oModel = Factory::model('Email', Constants::MODULE_SLUG);
            /** @var \Nails\Email\Service\Emailer $oEmailer */
            $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);

            $oResult = $oModel->getAllRawQuery([
                new Select(['id', 'ref']),
                new Where('status', Email::STATUS_PENDING),
                new Paginate(Config::get('EMAIL_SEND_PENDING_LIMIT', static::PER_PROCESS)),
            ]);

            if (!$oResult->num_rows()) {
                $oOutput->writeln('No pending email to send.');
                $oOutput->writeln('');
                return static::EXIT_CODE_SUCCESS;
            }

            while ($oEmail = $oResult->unbuffered_row()) {
                try {

                    $oOutput->write(sprintf(
                        'Sending <info>%s</info>... ',
                        $oEmail->ref
                    ));

                    if (!$oEmailer->doSend($oEmail->id)) {
                        throw new EmailerException($oEmailer->lastError());
                    }

                    $oOutput->writeln('<info>done</info>');

                } catch (\Throwable $e) {
                    $oOutput->writeln(sprintf(
                        '<error>%s</error>',
                        $e->getMessage()
                    ));
                }
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
