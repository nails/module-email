<?php

namespace Tests\Housekeeping;

use Nails\Common\Model\Base as ModelBase;
use Nails\Config;
use Nails\Email\Housekeeping\Archive;
use PHPUnit\Framework\TestCase;

abstract class ArchiveHarness extends Archive
{
    public function __construct(private readonly mixed $mLegacy)
    {
    }

    protected function model(): ModelBase
    {
        throw new \RuntimeException('ArchiveHarness does not load a model');
    }

    protected function legacyRetentionPeriod(): mixed
    {
        return $this->mLegacy;
    }

    public function resolvedDays(): int
    {
        return $this->retentionDays();
    }

    public function resolvedSource(): string
    {
        return $this->retentionSource();
    }
}

class ArchiveConfigSet extends ArchiveHarness
{
    const CONFIG_RETENTION_DAYS = 'EMAIL_ARCHIVE_RETENTION_DAYS_TEST_CONFIG';
}

class ArchiveLegacyOnly extends ArchiveHarness
{
    const CONFIG_RETENTION_DAYS = 'EMAIL_ARCHIVE_RETENTION_DAYS_TEST_LEGACY';
}

class ArchiveBothSet extends ArchiveHarness
{
    const CONFIG_RETENTION_DAYS = 'EMAIL_ARCHIVE_RETENTION_DAYS_TEST_BOTH';
}

class ArchiveUnset extends ArchiveHarness
{
    const CONFIG_RETENTION_DAYS = 'EMAIL_ARCHIVE_RETENTION_DAYS_TEST_UNSET';
}

/**
 * @covers \Nails\Email\Housekeeping\Archive
 */
class ArchiveTest extends TestCase
{
    public function test_config_wins_when_set(): void
    {
        Config::set(ArchiveConfigSet::CONFIG_RETENTION_DAYS, 30);

        $oRoutine = new ArchiveConfigSet(null);
        $aWarnings = $this->captureDeprecations(function () use ($oRoutine) {
            $oRoutine->resolvedDays();
        });

        $this->assertSame(30, $oRoutine->resolvedDays());
        $this->assertSame('config', $oRoutine->resolvedSource());
        $this->assertSame([], $aWarnings);
    }

    public function test_legacy_app_setting_is_used_and_deprecated(): void
    {
        $oRoutine = new ArchiveLegacyOnly(90);
        $aWarnings = $this->captureDeprecations(function () use ($oRoutine) {
            $oRoutine->resolvedDays();
        });

        $this->assertSame(90, $oRoutine->resolvedDays());
        $this->assertSame('app_setting', $oRoutine->resolvedSource());
        $this->assertCount(1, $aWarnings);
        $this->assertStringContainsString('retention_period', $aWarnings[0]);
        $this->assertStringContainsString(ArchiveLegacyOnly::CONFIG_RETENTION_DAYS, $aWarnings[0]);
    }

    public function test_config_wins_over_legacy_but_still_warns(): void
    {
        Config::set(ArchiveBothSet::CONFIG_RETENTION_DAYS, 30);

        $oRoutine = new ArchiveBothSet(90);
        $aWarnings = $this->captureDeprecations(function () use ($oRoutine) {
            $oRoutine->resolvedDays();
        });

        $this->assertSame(30, $oRoutine->resolvedDays());
        $this->assertSame('config', $oRoutine->resolvedSource());
        $this->assertCount(1, $aWarnings);
        $this->assertStringContainsString('retention_period', $aWarnings[0]);
    }

    public function test_unset_uses_the_class_default(): void
    {
        $oRoutine = new ArchiveUnset(null);
        $aWarnings = $this->captureDeprecations(function () use ($oRoutine) {
            $oRoutine->resolvedDays();
        });

        $this->assertSame(0, $oRoutine->resolvedDays());
        $this->assertSame('default', $oRoutine->resolvedSource());
        $this->assertSame([], $aWarnings);
    }

    public function test_resolution_is_memoised(): void
    {
        $oRoutine = new ArchiveLegacyOnly(14);
        $aWarnings = $this->captureDeprecations(function () use ($oRoutine) {
            $oRoutine->resolvedDays();
            $oRoutine->resolvedDays();
            $oRoutine->resolvedSource();
        });

        $this->assertSame(14, $oRoutine->resolvedDays());
        $this->assertCount(1, $aWarnings);
    }

    /**
     * @return string[]
     */
    private function captureDeprecations(callable $cCallback): array
    {
        $aSeen = [];
        set_error_handler(function (int $iSeverity, string $sMessage) use (&$aSeen): bool {
            if ($iSeverity === E_USER_DEPRECATED) {
                $aSeen[] = $sMessage;
                return true;
            }
            return false;
        });

        try {
            $cCallback();
        } finally {
            restore_error_handler();
        }

        return $aSeen;
    }
}
