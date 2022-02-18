<?php

namespace Nails\Email\Console\Command\Send;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Helper\Inflector;
use Nails\Common\Helper\Model\Limit;
use Nails\Common\Helper\Model\Paginate;
use Nails\Common\Helper\Model\Select;
use Nails\Common\Helper\Model\Sort;
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
 * Class Queued
 *
 * @package Nails\Email\Console\Command\Send
 */
class Queued extends Base
{
    const MAX_PER_PROCESS = 500;

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('email:send:queued')
            ->setDescription('Sends queued emails');
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

            $this->banner('Send queued email');

            /** @var \Nails\Email\Service\Emailer $oEmailer */
            $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);

            $oEmail = $this->getNextEmail();
            if (empty($oEmail)) {
                $oOutput->writeln('No queued email to send.');
                $oOutput->writeln('');
                return static::EXIT_CODE_SUCCESS;
            }

            $iSent  = 0;
            $iLimit = Config::get('EMAIL_SEND_QUEUED_MAX_PER_PROCESS', static::MAX_PER_PROCESS);

            do {

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

                } finally {
                    $iSent++;
                    $oEmail = $this->getNextEmail();
                }

            } while ($iSent < $iLimit && $oEmail);

            $oOutput->writeln('');

        } catch (ConsoleException $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                [$e->getMessage()]
            );
        }

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the next email to process
     *
     * @return \stdClass|null
     * @throws FactoryException
     * @throws ModelException
     */
    protected function getNextEmail(): ?\stdClass
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        /** @var \Nails\Email\Model\Email $oModel */
        $oModel = Factory::model('Email', Constants::MODULE_SLUG);

        $iTimestamp = (int) (microtime(true) * 1000);

        $oDb
            ->set('queue_process', $iTimestamp)
            ->where('status', $oModel::STATUS_QUEUED)
            ->where('queue_process', null)
            ->order_by('queue_priority')
            ->order_by('queued', $oModel::SORT_DESC)
            ->order_by('id')
            ->limit(1)
            ->update($oModel->getTableName());

        $oResult = $oModel->getAllRawQuery([
            new Select(['id', 'ref']),
            new Where('status', Email::STATUS_QUEUED),
            new Where('queue_process', $iTimestamp),
            new Limit(1),
        ]);

        return $oResult->num_rows()
            ? $oResult->unbuffered_row()
            : null;
    }
}
