<?php

namespace Nails\Email\Housekeeping;

use Nails\Common\Model\Base as ModelBase;
use Nails\Email\Constants;
use Nails\Email\Settings\General;
use Nails\Factory;
use Nails\Housekeeping\Routine\Base;
use Nails\Housekeeping\Routine\Context;
use Nails\Housekeeping\Routine\Result;
use Nails\Housekeeping\Traits\DeletesModelRows;

class Archive extends Base
{
    use DeletesModelRows {
        execute as deleteModelRows;
    }

    const LABEL           = 'Email archive';
    const DESCRIPTION     = 'Deletes archived emails older than the configured retention period';
    const CRON_EXPRESSION = '15 2 * * *';

    protected function model(): ModelBase
    {
        return Factory::model('Email', Constants::MODULE_SLUG);
    }

    /**
     * @return array<int, mixed>
     */
    protected function where(): array
    {
        $iDays = $this->retentionDays();
        if ($iDays < 1) {
            return [['id' => 0]];
        }

        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');
        $oNow->sub(new \DateInterval('P' . $iDays . 'D'));

        return [
            ['created <', $oNow->format('Y-m-d H:i:s')],
        ];
    }

    /**
     * @return string[]
     */
    protected function auditColumns(): array
    {
        return ['id', 'type', 'user_email', 'created'];
    }

    protected function optimizeAfter(): bool
    {
        return true;
    }

    public function execute(Context $oContext): Result
    {
        $iDays = $this->retentionDays();
        if ($iDays < 1) {
            $oContext
                ->writeln('Archive cleanup disabled')
                ->log('DISABLED retention_period=0');

            return Result::ok(0, 'Archive cleanup disabled');
        }

        $oContext->writeln('Retention policy: <info>' . $iDays . ' days</info>');

        return $this->deleteModelRows($oContext);
    }

    protected function retentionDays(): int
    {
        return (int) appSetting(General::KEY_RETENTION_PERIOD, Constants::MODULE_SLUG);
    }
}
