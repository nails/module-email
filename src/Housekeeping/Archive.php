<?php

namespace Nails\Email\Housekeeping;

use Nails\Common\Model\Base as ModelBase;
use Nails\Config;
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

    const LABEL                 = 'Email archive';
    const DESCRIPTION           = 'Deletes archived emails older than EMAIL_ARCHIVE_RETENTION_DAYS';
    const CRON_EXPRESSION       = '15 2 * * *';
    const CONFIG_RETENTION_DAYS = 'EMAIL_ARCHIVE_RETENTION_DAYS';
    const RETENTION_DAYS        = 0;

    private ?int $iRetentionDays = null;
    private string $sRetentionSource = 'default';

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
        $oContext->log(sprintf(
            'RETENTION days=%d source=%s',
            $iDays,
            $this->retentionSource()
        ));

        if ($iDays < 1) {
            $oContext
                ->writeln('Archive cleanup disabled')
                ->log('DISABLED ' . static::CONFIG_RETENTION_DAYS . '=0');

            return Result::ok(0, 'Archive cleanup disabled');
        }

        $oContext->writeln('Retention policy: <info>' . $iDays . ' days</info>');

        return $this->deleteModelRows($oContext);
    }

    protected function retentionDays(): int
    {
        if ($this->iRetentionDays !== null) {
            return $this->iRetentionDays;
        }

        $mLegacy = $this->legacyRetentionPeriod();

        if ($mLegacy !== null) {
            deprecatedError(
                sprintf('The "%s" app setting', General::KEY_RETENTION_PERIOD),
                static::CONFIG_RETENTION_DAYS
            );
        }

        if (Config::isSet(static::CONFIG_RETENTION_DAYS)) {
            $this->sRetentionSource = 'config';
            return $this->iRetentionDays = (int) Config::get(static::CONFIG_RETENTION_DAYS);

        } elseif ($mLegacy !== null) {
            $this->sRetentionSource = 'app_setting';
            return $this->iRetentionDays = (int) $mLegacy;
        }

        return $this->iRetentionDays = static::RETENTION_DAYS;
    }

    protected function retentionSource(): string
    {
        $this->retentionDays();
        return $this->sRetentionSource;
    }

    protected function legacyRetentionPeriod(): mixed
    {
        return appSetting(General::KEY_RETENTION_PERIOD, Constants::MODULE_SLUG);
    }
}
