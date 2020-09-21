<?php

/**
 * This file is the template for the contents of emails
 * Used by the console command when creating emails.
 */

return <<<'EOD'
<?php

/**
 * The {{CLASS_NAME_NORMALISED}} email
 *
 * @package  App
 * @category email
 */

namespace {{NAMESPACE}};

use Nails\Email\Factory\Email;

class {{CLASS_NAME}} extends Email
{
    protected $sType = '{{EMAIL_KEY}}';

    // --------------------------------------------------------------------------

    /**
     * Returns test data to use when sending test emails
     *
     * @return array
     */
    public function getTestData(): array
    {
        // @todo - complete this stub
        return [];
    }
}

EOD;
