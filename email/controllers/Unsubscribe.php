<?php

/**
 * This class allows users to subscribe and unsubscribe from individual email types
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Auth;
use Nails\Auth\Model\User;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\ViewNotFoundException;
use Nails\Common\Service\Encrypt;
use Nails\Common\Service\Input;
use Nails\Email\Constants;
use Nails\Email\Controller\Base;
use Nails\Email\Service\Emailer;
use Nails\Factory;

/**
 * Class Unsubscribe
 */
class Unsubscribe extends Base
{
    /**
     * Renders the subscribe/unsubscribe page
     *
     * @return void
     * @throws FactoryException
     * @throws ModelException
     * @throws ViewNotFoundException
     */
    public function index()
    {
        /** @var Input $input */
        $input = Factory::service('Input');
        /** @var Encrypt $encrypt */
        $encrypt = Factory::service('Encrypt');
        /** @var \Nails\Common\Service\View $viewService */
        $viewService = Factory::service('View');
        /** @var Emailer $emailer */
        $emailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var User $userModel */
        $userModel = Factory::model('User', Auth\Constants::MODULE_SLUG);

        $token = $input->get('token');
        if (empty($token)) {
            show404();
        }

        try {

            $tokenArr = $encrypt->decode($token);
            $tokenArr = explode('|', $tokenArr);

            if (count($tokenArr) != 3) {
                show404();
            }

        } catch (Throwable $e) {
            show404();
        }

        [$typeSlug, $emailRef, $userId] = $tokenArr;

        /** @var \Nails\Auth\Resource\User $user */
        $user = $userModel->getById($userId);
        if (empty($user)) {
            show404();
        }

        $type = $emailer->getType($typeSlug);
        if (empty($type) || !$type->canUnsubscribe()) {
            show404();
        }

        $email = $emailer->getByRef($emailRef);
        if (!$email) {
            show404();
        }

        // --------------------------------------------------------------------------

        $unsubscribed = $emailer->userHasUnsubscribed($user, $type);
        $title        = 'Are you sure?';
        $body         = 'Please confirm you\'d like to unsubscribe from <strong>' . $type->name . '</strong> emails.';
        $btnText      = 'Unsubscribe';
        $btnUrl       = 'email/unsubscribe?token=' . $token . '&confirm=1';

        //  All seems above board, action the request
        if ($input->get('confirm')) {
            $emailer->unsubscribeUser($user, $type);
            $title   = 'Unsubscribed';
            $body    = 'You have been succesfully unsubscribed from <strong>' . $type->name . '</strong> emails.';
            $btnText = 'Re-subscribe';
            $btnUrl  = 'email/unsubscribe?token=' . $token . '&undo=1';

        } elseif ($input->get('undo')) {
            $emailer->subscribeUser($user, $type);
            $title   = 'Subscribed';
            $body    = 'You have been succesfully re-subscribed to <strong>' . $type->name . '</strong> emails.';
            $btnText = 'Unsubscribe';
            $btnUrl  = 'email/unsubscribe?token=' . $token . '&confirm=1';
        }

        // --------------------------------------------------------------------------

        $this->loadStyles(NAILS_APP_PATH . 'application/modules/email/views/utilities/unsubscribe.php');

        $viewService
            ->setData([
                'logo'    => logoDiscover(),
                'title'   => $title,
                'body'    => $body,
                'btnText' => $btnText,
                'btnUrl'  => $btnUrl,
            ])
            ->load([
                'structure/header/blank',
                'email/utilities/unsubscribe',
                'structure/footer/blank',
            ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Map all requests to index
     *
     * @return  void
     * @throws FactoryException
     * @throws ModelException
     * @throws ViewNotFoundException
     */
    public function _remap()
    {
        $this->index();
    }
}
