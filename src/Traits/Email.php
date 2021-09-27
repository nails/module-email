<?php

namespace Nails\Email\Traits;

use Nails\Auth\Resource\User;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\ValidationException;
use Nails\Email\Constants;
use Nails\Email\Exception\EmailerException;
use Nails\Email\Service\Emailer;
use Nails\Factory;

trait Email
{
    /**
     * The email's type
     *
     * @var string
     */
    protected $sType = '';

    /**
     * The email's recipients
     *
     * @var array
     */
    protected $aTo = [];

    /**
     * The email's CC recipients
     *
     * @var array
     */
    protected $aCc = [];

    /**
     * The email's BCC recipients
     *
     * @var array
     */
    protected $aBcc = [];

    /**
     * The name of the sender
     *
     * @var string
     */
    protected $sFromName = '';

    /**
     * The email address of the sender (the reply-to value)
     *
     * @var string
     */
    protected $sFromEmail = '';

    /**
     * The email's subject
     *
     * @var string
     */
    protected $sSubject = '';

    /**
     * The email's data payload
     *
     * @var array
     */
    protected $aData = [];

    /**
     * The email's attachments
     *
     * @var array
     */
    protected $aAttachments = [];

    /**
     * Whether the last email was sent successfully or not
     *
     * @var bool|null
     */
    protected $bLastEmailDidSend;

    /**
     * The emails which were generated using this template (reset before each call to send())
     *
     * @var array
     */
    protected $aEmailsGenerated = [];

    // --------------------------------------------------------------------------

    /**
     * Email constructor.
     */
    public function __construct()
    {
        Factory::helper('email');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the email's type
     *
     * @param string $sType The type of email to send
     *
     * @return \Nails\Email\Factory\Email
     */
    public function type($sType): self
    {
        $this->sType = $sType;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Return's the email's type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->sType;
    }

    // --------------------------------------------------------------------------

    /**
     * Add a recipient
     *
     * @param array|int|string|User $mUserIdOrEmail The user ID to send to, a user object, an email, or an array
     * @param bool                  $bAppend        Whether to add to the list of recipients or not
     *
     * @return $this
     */
    public function to($mUserIdOrEmail, $bAppend = false): self
    {
        return $this->addRecipient($mUserIdOrEmail, $bAppend, $this->aTo);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being sent to
     *
     * @return int[]|string[]|User[]
     */
    public function getTo(): array
    {
        return $this->getRecipients($this->aTo);
    }

    // --------------------------------------------------------------------------

    /**
     * Add a recipient (on CC)
     *
     * @param array|int|string|User $mUserIdOrEmail The user ID to send to, a user object, an email, or an array
     * @param bool                  $bAppend        Whether to add to the list of recipients or not
     *
     * @return $this
     */
    public function cc($mUserIdOrEmail, $bAppend = false): self
    {
        return $this->addRecipient($mUserIdOrEmail, $bAppend, $this->aCc);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being CC'd to
     *
     * @return int[]|string[]|User[]
     */
    public function getCc(): array
    {
        return $this->getRecipients($this->aCc);
    }

    // --------------------------------------------------------------------------

    /**
     * Add a recipient (on BCC)
     *
     * @param array|int|string|User $mUserIdOrEmail The user ID to send to, a user object, an email, or an array
     * @param bool                  $bAppend        Whether to add to the list of recipients or not
     *
     * @return $this
     */
    public function bcc($mUserIdOrEmail, $bAppend = false): self
    {
        return $this->addRecipient($mUserIdOrEmail, $bAppend, $this->aBcc);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being BCC'd to
     *
     * @return int[]|string[]|User[]
     */
    public function getBcc(): array
    {
        return $this->getRecipients($this->aBcc);
    }

    // --------------------------------------------------------------------------

    /**
     * Sets who the email is from
     *
     * @param string $sEmail The email address to send from
     * @param string $sName  The name to send from
     *
     * @return $this
     * @throws ValidationException
     */
    public function from(string $sEmail, string $sName = ''): self
    {
        $this->validateEmail($sEmail);
        $this->sFromEmail = $sEmail;
        $this->sFromName  = $sName;

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being sent from (email)
     *
     * @return string
     */
    public function getFromEmail(): string
    {
        return $this->sFromEmail;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being sent from (name)
     *
     * @return string
     */
    public function getFromName(): string
    {
        return $this->sFromName;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the subject
     *
     * @return $this
     */
    public function subject(string $sSubject): self
    {
        $this->sSubject = $sSubject;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the subject
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->sSubject;
    }

    // --------------------------------------------------------------------------

    /**
     * Set email payload data
     *
     * @param iterable|string $mKey   An iterable of key value pairs, or the key if supplying the second parameter
     * @param mixed           $mValue The value
     *
     * @return $this
     */
    public function data($mKey, $mValue = null): self
    {
        if (is_array($mKey) || is_iterable($mKey) || $mKey instanceof \stdClass) {
            foreach ($mKey as $sKey => $mValue) {
                $this->data($sKey, $mValue);
            }
        } else {
            $this->aData[$mKey] = $mValue;
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns email data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->aData;
    }

    // --------------------------------------------------------------------------

    /**
     * Add an attachment to an email
     *
     * @param string|string[] $sPath     The path to the attachment
     * @param string          $sFileName The name to give the filename
     *
     * @return $this
     */
    public function attach($sPath, string $sFileName = null): self
    {
        if (is_array($sPath)) {
            foreach ($sPath as $datum) {
                $this->attach($datum);
            }
        } else {
            $this->aAttachments[] = [$sPath, $sFileName ?? basename($sPath)];
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns email attachments
     *
     * @return string[]
     */
    public function getAttachments(): array
    {
        return $this->aAttachments;
    }

    // --------------------------------------------------------------------------

    /**
     * Send the email now
     *
     * @return $this
     * @throws FactoryException
     * @throws ModelException
     * @throws EmailerException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send(): self
    {
        return $this->doSend(true);
    }

    // --------------------------------------------------------------------------

    /**
     * Send the email soon, via cron
     *
     * @return $this
     * @throws FactoryException
     * @throws ModelException
     * @throws EmailerException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendSoon(): self
    {
        return $this->doSend(false);
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the last email was sent successfully
     *
     * @return bool
     */
    public function didSend(): bool
    {
        return (bool) $this->bLastEmailDidSend;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the emails generated using the template (reset before each call to send())
     *
     * @return array
     */
    public function getGeneratedEmails(): array
    {
        return $this->aEmailsGenerated;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the item as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'sType'        => $this->getType(),
            'aTo'          => $this->getTo(),
            'aCc'          => $this->getCc(),
            'aBcc'         => $this->getBcc(),
            'sFromName'    => $this->getFromName(),
            'sFromEmail'   => $this->getFromEmail(),
            'sSubject'     => $this->getSubject(),
            'aData'        => $this->getData(),
            'aAttachments' => $this->getAttachments(),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a recipient
     *
     * @param int|string|User $mUserIdOrEmail The user ID to send to, or an email address
     * @param bool            $bAppend        Whether to add to the list of recipients or not
     * @param array           $aArray         The array to add the recipient to
     *
     * @return $this
     */
    protected function addRecipient($mUserIdOrEmail, $bAppend, &$aArray): self
    {
        if (!$bAppend) {
            $aArray = [];
        }

        if (is_string($mUserIdOrEmail) && preg_match('/[,;]/', $mUserIdOrEmail)) {
            $mUserIdOrEmail = array_filter(array_map('trim', preg_split('/[,;]/', $mUserIdOrEmail)));
        }

        if (is_array($mUserIdOrEmail)) {
            foreach ($mUserIdOrEmail as $sUserIdOrEmail) {
                $this->addRecipient($sUserIdOrEmail, true, $aArray);
            }

        } elseif ($mUserIdOrEmail instanceof User) {
            $aArray[] = $mUserIdOrEmail;

        } elseif (is_int($mUserIdOrEmail)) {
            $aArray[] = $mUserIdOrEmail;

        } else {
            $this->validateEmail($mUserIdOrEmail);
            $aArray[] = $mUserIdOrEmail;
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns filtered recipients
     *
     * @param array $aArray The array to return from
     *
     * @return array
     */
    protected function getRecipients($aArray): array
    {
        return array_values(array_filter(array_unique($aArray)));
    }

    // --------------------------------------------------------------------------

    /**
     * Validates an email address
     *
     * @param int|string $sEmail The email address to validate
     *
     * @throws ValidationException
     */
    protected function validateEmail($sEmail): void
    {
        if (empty($sEmail)) {
            throw new ValidationException('No email address supplied');
        } elseif (is_string($sEmail) && !valid_email($sEmail)) {
            throw new ValidationException('"' . $sEmail . '" is not a valid email');
        }
    }

    // --------------------------------------------------------------------------

    protected function compileEmailForSend(): array
    {
        $aEmail = $this->toArray();
        $oData  = (object) [
            'type'     => $aEmail['sType'],
            'to_id'    => null,
            'to_email' => null,
            'data'     => (object) $aEmail['aData'],
        ];

        if (!empty($aEmail['aAttachments'])) {
            $oData->data->attachments = $aEmail['aAttachments'];
        }

        if (!empty($aEmail['sSubject']) && empty($oData->data->email_subject)) {
            $oData->data->email_subject = $aEmail['sSubject'];
        }

        if (!empty($aEmail['sFromName']) && empty($oData->data->email_from_name)) {
            $oData->data->email_from_name = $aEmail['sFromName'];
        }

        if (!empty($aEmail['sFromEmail']) && empty($oData->data->email_from_email)) {
            $oData->data->email_from_email = $aEmail['sFromEmail'];
        }

        if (!empty($aEmail['aCc']) && empty($oData->data->cc)) {
            $oData->data->cc = $aEmail['aCc'];
        }

        if (!empty($aEmail['aBcc']) && empty($oData->data->bcc)) {
            $oData->data->bcc = $aEmail['aBcc'];
        }

        $aOut = [];
        foreach ($aEmail['aTo'] as $mUserIdOrEmail) {

            $oData->to_id    = null;
            $oData->to_email = null;

            if ($mUserIdOrEmail instanceof User) {
                $oData->to_id = $mUserIdOrEmail->id;

            } elseif (is_numeric($mUserIdOrEmail)) {
                $oData->to_id = $mUserIdOrEmail;

            } elseif (valid_email($mUserIdOrEmail)) {
                $oData->to_email = $mUserIdOrEmail;
            }

            $aOut[] = (array) (clone $oData);
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Processes the email sending
     *
     * @param bool $bSendNow Whether to send the email now, or via cron
     *
     * @return $this
     * @throws \Nails\Common\Exception\FactoryException
     * @throws \Nails\Common\Exception\ModelException
     * @throws \Nails\Email\Exception\EmailerException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    protected function doSend(bool $bSendNow): self
    {
        $aEmails = $this->compileEmailForSend();

        /** @var Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);

        $this->aEmailsGenerated = [];

        foreach ($aEmails as $aData) {

            $this->bLastEmailDidSend = $oEmailer->send($aData, false, $bSendNow);
            $oLastEmail              = $oEmailer->getLastEmail();

            /**
             * There might not always be an email generated:
             * - unsubscribed users will not received an email, and send() will not fail
             * - suspended users will not receive an email, and send() will not fail
             */
            if (!empty($oLastEmail)) {
                $this->aEmailsGenerated[] = clone $oLastEmail;;
            }
        }

        return $this;
    }
}
