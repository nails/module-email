<?php

namespace Nails\Email\Console\Command;

use Nails\Common\Helper\Inflector;
use Nails\Common\Helper\Strings;
use Nails\Console\Command\Base;
use Nails\Console\Exception\ConsoleException;
use Nails\Email\Constants;
use Nails\Email\Exception\EmailerException;
use Nails\Factory;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Resend
 *
 * @package Nails\Email\Console\Command
 */
class Resend extends Base
{
    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('email:resend')
            ->setDescription('Resends an email')
            ->addArgument('ids', InputArgument::REQUIRED, 'A comma separated list of email IDs or references to resend');
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

        $this->banner('Resend email');

        /** @var \Nails\Email\Service\Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);

        $aIds = array_unique(Strings::toArray($oInput->getArgument('ids') ?? ''));

        foreach ($aIds as $sId) {
            try {
                if (is_numeric($sId)) {
                    $oOutput->write(sprintf(
                        'Attempting to resend email with ID <info>%s</info>... ',
                        number_format($sId)
                    ));
                    $oEmail = $oEmailer->getById($sId);
                } else {
                    $oOutput->write(sprintf(
                        'Attempting to resend email with ref <info>%s</info>... ',
                        $sId
                    ));
                    $oEmail = $oEmailer->getByRef($sId);
                }

                if (empty($oEmail)) {
                    throw new EmailerException('Does not exist');
                }

                if (!$oEmailer->resend($oEmail->id)) {
                    throw new EmailerException($oEmailer->lastError());
                }

                $oOutput->writeln(sprintf(
                    '<info>done</info>; <comment>%s</comment>',
                    $oEmailer->getLastEmail()->data->url->viewOnline ?? 'No URL available'
                ));

            } catch (\Throwable $e) {
                $oOutput->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            }
        }

        $oOutput->writeln('');

        return self::EXIT_CODE_SUCCESS;
    }
}
