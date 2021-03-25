<?php

namespace Nails\Email\Console\Command\Archive;

use DateTime;
use Nails\Common\Service\Database;
use Nails\Console\Command\Base;
use Nails\Console\Exception\ConsoleException;
use Nails\Email\Constants;
use Nails\Email\Service\Emailer;
use Nails\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Clean
 *
 * @package Nails\Email\Console\Command\Archive
 */
class Clean extends Base
{
    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('email:archive:clean')
            ->setDescription('Cleans the archive according to data retention rules');
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

            $this->banner('Email: Archive Clean');

            $iRetention = (int) appSetting('retention_period', Constants::MODULE_SLUG);
            if ($iRetention) {

                $oOutput->writeln('Retention policy: <info>' . $iRetention . ' days</info>');

                /** @var Database $oDb */
                $oDb = Factory::service('Database');
                /** @var DateTime $oNow */
                $oNow = Factory::factory('DateTime');
                /** @var Emailer $oEmailer */
                $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);

                $oNow->sub(new \DateInterval('P' . $iRetention . 'D'));

                $oOutput->write('Cleaning items older than <comment>' . $oNow->format('Y-m-d H:i:s') . '</comment>... ');

                $oDb->where('sent <', $oNow->format('Y-m-d H:i:s'));
                $oDb->from($oEmailer->getTableName());
                $oResult = $oDb->delete();

                $oOutput->writeln('<comment>done</comment>');
                $oOutput->writeln('<comment>' . $oDb->affected_rows() . '</comment> items deleted');

            } else {
                $oOutput->writeln('Archive cleanup disabled');
            }

        } catch (ConsoleException $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                [$e->getMessage()]
            );
        }

        // --------------------------------------------------------------------------

        //  And we're done
        $oOutput->writeln('');
        $oOutput->writeln('Complete!');

        return self::EXIT_CODE_SUCCESS;
    }
}
