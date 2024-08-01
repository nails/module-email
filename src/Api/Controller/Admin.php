<?php

namespace Nails\Email\Api\Controller;

use Nails\Api;
use Nails\Api\Controller\Base;
use Nails\Api\Exception\ApiException;
use Nails\Api\Factory\ApiResponse;
use Nails\Auth;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Service\HttpCodes;
use Nails\Email\Constants;
use Nails\Email\Resource\Type;
use Nails\Email\Service\Emailer;
use Nails\Factory;

class Admin extends Base
{
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    /**
     * @throws ApiException
     * @throws FactoryException
     * @throws ModelException
     */
    public function putSubscribe(): ApiResponse
    {
        if (!userHasPermission('admin:email:subscriptions:delete')) {
            throw new Api\Exception\ApiException(
                'Ypu do not have permission to subscribe a user',
                HttpCodes::STATUS_UNAUTHORIZED
            );
        }

        /** @var Emailer $emailer */
        $emailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var ApiResponse $response */
        $response = Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG);

        $user = $this->getUser();
        $type = $this->getType();

        if (!$emailer->subscribeUser($user, $type)) {
            throw new Api\Exception\ApiException(
                'Failed to subscribe user',
                HttpCodes::STATUS_INTERNAL_SERVER_ERROR
            );
        }

        return $response;
    }

    // --------------------------------------------------------------------------

    /**
     * @throws ApiException
     * @throws FactoryException
     * @throws ModelException
     */
    public function putUnsubscribe(): ApiResponse
    {
        if (!userHasPermission('admin:email:subscriptions:create')) {
            throw new Api\Exception\ApiException(
                'Ypu do not have permission to unsubscribe a user',
                HttpCodes::STATUS_UNAUTHORIZED
            );
        }

        /** @var Emailer $emailer */
        $emailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var ApiResponse $response */
        $response = Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG);

        $user = $this->getUser();
        $type = $this->getType();

        if (!$type->canUnsubscribe()) {
            throw new Api\Exception\ApiException(
                'This email cannot be unsubscribed from.',
                HttpCodes::STATUS_METHOD_NOT_ALLOWED
            );
        }

        if (!$emailer->unsubscribeUser($user, $type)) {
            throw new Api\Exception\ApiException(
                'Failed to unsubscribe user',
                HttpCodes::STATUS_INTERNAL_SERVER_ERROR
            );
        }

        return $response;
    }

    // --------------------------------------------------------------------------

    /**
     * @throws ApiException
     * @throws FactoryException
     * @throws ModelException
     */
    private function getUser(): Auth\Resource\User
    {
        /** @var Auth\Model\User $model */
        $model = Factory::model('User', Auth\Constants::MODULE_SLUG);

        $data = $this->getRequestData();

        $user = $model->getById($data['userId'] ?? null);
        if (empty($user)) {
            throw new Api\Exception\ApiException(
                'Invalid User ID',
                HttpCodes::STATUS_BAD_REQUEST
            );
        }

        return $user;
    }

    // --------------------------------------------------------------------------

    /**
     * @throws ApiException
     * @throws FactoryException
     */
    private function getType(): Type
    {
        /** @var Emailer $emailer */
        $emailer = Factory::service('Emailer', Constants::MODULE_SLUG);

        $data = $this->getRequestData();

        $type = $emailer->getType($data['type'] ?? '');
        if (empty($type)) {
            throw new Api\Exception\ApiException(
                'Invalid Email Type',
                HttpCodes::STATUS_BAD_REQUEST
            );
        }

        return $type;
    }
}
