<?php

namespace Nails\Email\Interfaces;

use Nails\Auth\Resource\User;

interface Email
{
    /**
     * Set the email's type
     *
     * @param string $sType The type of email to send
     *
     * @return $this
     */
    public function type($sType): self;

    // --------------------------------------------------------------------------

    /**
     * Return's the email's type
     *
     * @return string
     */
    public function getType(): string;

    // --------------------------------------------------------------------------

    /**
     * Add a recipient
     *
     * @param array|int|string|User $mUserIdOrEmail The user ID to send to, a user object, an email, or an array
     * @param bool                  $bAppend        Whether to add to the list of recipients or not
     *
     * @return $this
     */
    public function to($mUserIdOrEmail, $bAppend = false): self;

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being sent to
     *
     * @return int[]|string[]|User[]
     */
    public function getTo(): array;

    // --------------------------------------------------------------------------

    /**
     * Add a recipient (on CC)
     *
     * @param array|int|string|User $mUserIdOrEmail The user ID to send to, a user object, an email, or an array
     * @param bool                  $bAppend        Whether to add to the list of recipients or not
     *
     * @return $this
     */
    public function cc($mUserIdOrEmail, $bAppend = false): self;

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being CC'd to
     *
     * @return int[]|string[]|User[]
     */
    public function getCc(): array;

    // --------------------------------------------------------------------------

    /**
     * Add a recipient (on BCC)
     *
     * @param array|int|string|User $mUserIdOrEmail The user ID to send to, a user object, an email, or an array
     * @param bool                  $bAppend        Whether to add to the list of recipients or not
     *
     * @return $this
     */
    public function bcc($mUserIdOrEmail, $bAppend = false): self;

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being BCC'd to
     *
     * @return int[]|string[]|User[]
     */
    public function getBcc(): array;

    // --------------------------------------------------------------------------

    /**
     * Sets who the email is from
     *
     * @param string $sEmail The email address to send from
     * @param string $sName  The name to send from
     *
     * @return $this
     */
    public function from(string $sEmail, string $sName = ''): self;

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being sent from (email)
     *
     * @return string
     */
    public function getFromEmail(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns who the email is being sent from (name)
     *
     * @return string
     */
    public function getFromName(): string;

    // --------------------------------------------------------------------------

    /**
     * Sets the subject
     *
     * @return $this
     */
    public function subject(string $sSubject): self;

    // --------------------------------------------------------------------------

    /**
     * Returns the subject
     *
     * @return string
     */
    public function getSubject(): string;

    // --------------------------------------------------------------------------

    /**
     * Set email payload data
     *
     * @param iterable|string $mKey   An iterable of key value pairs, or the key if supplying the second parameter
     * @param mixed           $mValue The value
     *
     * @return $this
     */
    public function data($mKey, $mValue = null): self;

    // --------------------------------------------------------------------------

    /**
     * Returns email data
     *
     * @return array
     */
    public function getData(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns test data to use when sending test emails
     *
     * @return array
     */
    public function getTestData(): array;

    // --------------------------------------------------------------------------

    /**
     * Add an attachment to an email
     *
     * @param string|string[] $sPath     The path to the attachment
     * @param string          $sFileName The name to give the filename
     *
     * @return $this
     */
    public function attach($sPath, string $sFileName = null): self;

    // --------------------------------------------------------------------------

    /**
     * Returns email attachments
     *
     * @return string[]
     */
    public function getAttachments(): array;

    // --------------------------------------------------------------------------

    /**
     * Send the email
     *
     * @return $this
     */
    public function send(): self;

    // --------------------------------------------------------------------------

    /**
     * Whether the last email was sent successfully
     *
     * @return bool
     */
    public function didSend(): bool;

    // --------------------------------------------------------------------------

    /**
     * Returns the emails generated using the template (reset before each call to send())
     *
     * @return array
     */
    public function getGeneratedEmails(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns the item as an array
     *
     * @return array
     */
    public function toArray(): array;
}
