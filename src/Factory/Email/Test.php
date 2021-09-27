<?php

namespace Nails\Email\Factory\Email;

use Nails\Common\Exception\FactoryException;
use Nails\Email\Interfaces;
use Nails\Email\Traits;
use Nails\Factory;

/**
 * Class Test
 *
 * @package Nails\Email\Factory\Email
 */
class Test implements Interfaces\Email
{
    use Traits\Email;

    // --------------------------------------------------------------------------

    /**
     * Construct Test
     */
    public function __construct()
    {
        $this->type('test_email');
    }

    // --------------------------------------------------------------------------

    /**
     * Returns test data to use when sending test emails
     *
     * @return array
     * @throws FactoryException
     */
    public function getTestData(): array
    {
        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        return [
            'sentAt' => $oNow->format('Y-m-d H:i:s'),
        ];
    }
}
