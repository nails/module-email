<?php

namespace Nails\Housekeeping\Routine {

    abstract class Base
    {
    }

    class Context
    {
        public function isDryRun(): bool
        {
            return false;
        }

        public function writeln(string $sLine = ''): self
        {
            return $this;
        }

        public function log(string $sMessage): self
        {
            return $this;
        }
    }

    class Result
    {
        public static function ok(int $iProcessed = 0, string $sMessage = ''): self
        {
            return new self();
        }
    }
}

namespace Nails\Housekeeping\Traits {

    trait DeletesModelRows
    {
        public function execute(\Nails\Housekeeping\Routine\Context $oContext): \Nails\Housekeeping\Routine\Result
        {
            return \Nails\Housekeeping\Routine\Result::ok();
        }
    }
}
