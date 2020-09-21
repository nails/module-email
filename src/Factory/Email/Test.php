<?php

namespace Nails\Email\Factory\Email;

use Nails\Common\Exception\FactoryException;
use Nails\Email\Factory\Email;
use Nails\Factory;

/**
 * Class Test
 *
 * @package Nails\Email\Factory\Email
 */
class Test extends Email
{
    protected $sType = 'test_email';

    // --------------------------------------------------------------------------

    /**
     * Returns test data to use when sending test emails
     *
     * @return array
     * @throws FactoryException
     */
    public function getTestData(): array
    {
        return [
            'sentAt' => Factory::factory('DateTime')->format('Y-m-d H:i:s'),
        ];
    }
}
