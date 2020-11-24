<?php

/**
 * This class manages email template overrides
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Email;

use Nails\Admin\Controller\Base;
use Nails\Admin\Helper;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Exception\ViewNotFoundCaseException;
use Nails\Common\Exception\ViewNotFoundException;
use Nails\Common\Service\FormValidation;
use Nails\Common\Service\Input;
use Nails\Common\Service\Session;
use Nails\Common\Service\Uri;
use Nails\Common\Service\View;
use Nails\Email\Constants;
use Nails\Email\Exception\EmailerException;
use Nails\Email\Model\Template\Override;
use Nails\Email\Resource\Type;
use Nails\Email\Service\Emailer;
use Nails\Factory;

/**
 * Class Templates
 *
 * @package Nails\Admin\Email
 */
class Templates extends Base
{
    /**
     * Announces this controller's navGroups
     *
     * @return \stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
        $oNavGroup->setLabel('Email');
        $oNavGroup->setIcon('fa-paper-plane');

        if (userHasPermission('admin:email:templates:edit')) {
            $oNavGroup->addAction('Manage Templates');
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     *
     * @return array
     */
    public static function permissions(): array
    {
        $aPermissions = parent::permissions();

        $aPermissions['edit'] = 'Can edit templates';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Email settings
     *
     * @return void
     */
    public function index(): void
    {
        if (!userHasPermission('admin:email:templates:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Templates';

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $iPage      = $oInput->get('page') ? $oInput->get('page') : 0;
        $iPerPage   = $oInput->get('perPage') ? $oInput->get('perPage') : 50;
        $sSortOn    = $oInput->get('sortOn') ? $oInput->get('sortOn') : 'name';
        $sSortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'desc';
        $sKeywords  = $oInput->get('keywords') ? $oInput->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $aSortColumns = [
            'name'        => 'Label',
            'description' => 'Description',
        ];
        // --------------------------------------------------------------------------

        $aTypes = array_filter(
            $oEmailer->getTypes(),
            function ($oEmail) {
                return empty($oEmail->is_hidden);
            }
        );

        //  Filter out keywords
        if (!empty($sKeywords)) {
            $aTypes = array_filter(
                $aTypes,
                function ($oType) use ($sKeywords) {

                    if (stripos($oType->slug, $sKeywords) !== false) {
                        return true;
                    } elseif (stripos($oType->name, $sKeywords) !== false) {
                        return true;
                    } elseif (stripos($oType->description, $sKeywords) !== false) {
                        return true;
                    }

                    return false;
                }
            );
            $aTypes = array_filter($aTypes);
            $aTypes = array_values($aTypes);
        }

        $iTotal = count($aTypes);

        //  Sort
        arraySortMulti($aTypes, $sSortOn);
        if ($sSortOrder === 'DESC') {
            $aTypes = array_reverse($aTypes);
        }
        $aTypes = array_values($aTypes);

        //  Select Page
        $iPage--;
        $iPage = $iPage < 0 ? 0 : $iPage;

        //  Select page
        $iOffset = $iPage * $iPerPage;
        $aTypes  = array_slice($aTypes, $iOffset, $iPerPage);

        //  Pass to view
        $this->data['items'] = $aTypes;

        // --------------------------------------------------------------------------

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(
            true,
            $aSortColumns,
            $sSortOn,
            $sSortOrder,
            $iPerPage,
            $sKeywords
        );
        $this->data['pagination'] = Helper::paginationObject($iPage, $iPerPage, $iTotal);

        // --------------------------------------------------------------------------

        //  Mimic $aConfig
        $this->data['CONFIG'] = [
            'BASE_URL'              => 'admin/email/templates',
            'PERMISSION'            => null,
            'INDEX_PAGE_ID'         => null,
            'INDEX_FIELDS'          => [
                'Label'       => 'name',
                'Provided By' => function (Type $oType) {
                    return sprintf(
                        '<code>%s</code>',
                        $oType->component->name
                    );
                },
                'Description' => 'description',
            ],
            'INDEX_BOOL_FIELDS'     => [],
            'INDEX_USER_FIELDS'     => [],
            'INDEX_NUMERIC_FIELDS'  => [],
            'INDEX_CENTERED_FIELDS' => [],
            'INDEX_ROW_BUTTONS'     => [
                [
                    'url'   => 'edit/{{slug}}',
                    'label' => lang('action_edit'),
                    'class' => 'btn-primary',
                ],
                [
                    'url'   => 'reset/{{slug}}',
                    'label' => lang('action_reset'),
                    'class' => 'btn-warning confirm',
                ],
                [
                    'url'     => 'preview/{{slug}}',
                    'label'   => lang('action_preview'),
                    'class'   => 'btn-default',
                    'attr'    => 'rel="tipsy" title="Sends a test email to your email using this template."',
                    'enabled' => function (Type $oType) {
                        return !empty($oType->factory);
                    },
                ],
            ],
            'MODEL_INSTANCE'        => (object) [],
        ];

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws ModelException
     * @throws ViewNotFoundCaseException
     * @throws ViewNotFoundException
     */
    public function edit(): void
    {
        if (!userHasPermission('admin:email:templates:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Override $oOverrideModel */
        $oOverrideModel = Factory::model('TemplateOverride', Constants::MODULE_SLUG);

        $oType = $oEmailer->getType($oUri->segment(5));
        if (empty($oType)) {
            show404();
        }
        $oTypeFactory = $oType->getFactory();

        $oOverride = $oOverrideModel->getBySlug($oType->slug);

        /** @var View $oView */
        $oView     = Factory::service('View');
        $sSubject  = $this->normalise($oType->default_subject);
        $sBodyHtml = $this->normalise(file_get_contents($oView->resolvePath($oType->template_body)));
        $sBodyText = $this->normalise(file_get_contents($oView->resolvePath($oType->template_body . '_plaintext')));

        if ($oInput->post()) {

            try {

                /** @var FormValidation $oFormValidation */
                $oFormValidation = Factory::service('FormValidation');
                $oFormValidation
                    ->buildValidator([
                        'subject'   => [
                            function ($sValue) {
                                $this->detectPhp($sValue);
                            },
                        ],
                        'body_html' => [
                            function ($sValue) {
                                $this->detectPhp($sValue);
                            },
                        ],
                        'body_text' => [
                            function ($sValue) {
                                $this->detectPhp($sValue);
                            },
                        ],
                    ])
                    ->run();

                $aData = [
                    'subject'   => $this->normalise($oInput->post('subject')),
                    'body_html' => $this->normalise($oInput->post('body_html')),
                    'body_text' => $this->normalise($oInput->post('body_text')),
                ];

                if (empty($aData['subject']) || mb_strlen($aData['subject']) === mb_strlen($oType->default_subject)) {
                    unset($aData['subject']);
                }

                if (empty($aData['body_html']) || mb_strlen($aData['body_html']) === mb_strlen($sBodyHtml)) {
                    unset($aData['body_html']);
                }

                if (empty($aData['body_text']) || mb_strlen($aData['body_text']) === mb_strlen($sBodyText)) {
                    unset($aData['body_text']);
                }

                if (empty($aData) && !empty($oOverride)) {
                    $oOverrideModel->delete($oOverride->id);
                } elseif (!empty($aData)) {

                    //  Ensure $aData has all fields
                    $aData = array_merge(
                        [
                            'slug'                    => $oType->slug,
                            'subject'                 => null,
                            'subject_original_hash'   => md5($sSubject),
                            'body_html'               => null,
                            'body_html_original_hash' => md5($sBodyHtml),
                            'body_text'               => null,
                            'body_text_original_hash' => md5($sBodyText),
                        ],
                        array_filter($aData)
                    );

                    if (!empty($oOverride)) {
                        if (!$oOverrideModel->update($oOverride->id, $aData)) {
                            throw new ValidationException(
                                'Failed to update override. ' . $oOverrideModel->lastError()
                            );
                        }
                    } elseif (!empty($aData)) {
                        if (!$oOverrideModel->create($aData)) {
                            throw new ValidationException(
                                'Failed to create override. ' . $oOverrideModel->lastError()
                            );
                        }
                    }
                }

                /** @var Session $oSession */
                $oSession = Factory::service('Session');
                $oSession->setFlashData('success', 'Override updated successfully.');
                redirect('admin/email/templates/edit/' . $oType->slug);

            } catch (\Exception $e) {
                $this->data['error'] = 'Failed to set override. ' . $e->getMessage();
            }
        }

        $this->data['mTestData']        = $oTypeFactory ? $oTypeFactory->getTestData() : null;
        $this->data['sDefaultSubject']  = $sSubject;
        $this->data['sDefaultBodyHtml'] = $sBodyHtml;
        $this->data['sDefaultBodyText'] = $sBodyText;

        if (!empty($oOverride)) {
            $this->data['bDefaultSubjectChanged']  = $oOverride->subject_original_hash !== md5($sSubject);
            $this->data['bDefaultBodyHtmlChanged'] = $oOverride->body_html_original_hash !== md5($sBodyHtml);
            $this->data['bDefaultBodyTextChanged'] = $oOverride->body_text_original_hash !== md5($sBodyText);
        } else {
            $this->data['bDefaultSubjectChanged']  = false;
            $this->data['bDefaultBodyHtmlChanged'] = false;
            $this->data['bDefaultBodyTextChanged'] = false;
        }

        $this->data['oType']       = $oType;
        $this->data['oOverride']   = $oOverride;
        $this->data['page']->title = 'Edit Template for: ' . $oType->name;
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Normalises a string
     *
     * @param string $sString The string to normalise
     *
     * @return string
     */
    protected function normalise($sString): string
    {
        $sString = preg_replace("/\r/", '', $sString);
        return $sString;
    }

    // --------------------------------------------------------------------------

    /**
     * Detects if a string contains PHP
     *
     * @param string $sValue The string to test
     *
     * @throws ValidationException
     */
    protected function detectPhp(string $sValue): void
    {
        if (preg_match('/<\?|<\?php|<\?=/', $sValue)) {
            throw new ValidationException(
                'PHP is not supported in template overrides. ' .
                'Use <a href="https://mustache.github.io/mustache.5.html" target="_blank">Mustache</a> for ' .
                'variable substitution and simple logic.'
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Removes an override
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function reset(): void
    {
        if (!userHasPermission('admin:email:templates:edit')) {
            unauthorised();
        }

        /** @var Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Override $oOverrideModel */
        $oOverrideModel = Factory::model('TemplateOverride', Constants::MODULE_SLUG);
        /** @var Session $oSession */
        $oSession = Factory::service('Session');

        $oType = $oEmailer->getType($oUri->segment(5));
        if (empty($oType)) {
            show404();
        }

        $oOverride = $oOverrideModel->getBySlug($oType->slug);
        if (!empty($oOverride)) {
            try {

                if (!$oOverrideModel->delete($oOverride->id)) {
                    throw new ModelException(
                        'Failed to delete override. ' . $oOverrideModel->lastError()
                    );
                }

                $oSession->setFlashData('success', 'Template reset successfully.');

            } catch (\Exception $e) {
                $oSession->setFlashData('error', $e->getMessage());
            }
        } else {
            $oSession->setFlashData('success', 'Template reset successfully.');
        }

        redirect('admin/email/templates/index');
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a preview of the email using test data
     *
     * @throws FactoryException
     */
    public function preview(): void
    {
        if (!userHasPermission('admin:email:templates:edit')) {
            unauthorised();
        }

        /** @var Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Session $oSession */
        $oSession = Factory::service('Session');

        $oType = $oEmailer->getType($oUri->segment(5));
        if (empty($oType)) {
            show404();
        }

        $oEmail = $oType->getFactory();
        if (empty($oEmail)) {
            show404();
        }

        try {

            $oEmail
                ->to(activeUser())
                ->data($oEmail->getTestData())
                ->send();

            $aGeneratedEmails = $oEmail->getGeneratedEmails();

            if (empty($aGeneratedEmails)) {
                throw new EmailerException(
                    'No emails were generated when sending the test. Perhaps you have blocked this type of email?'
                );
            }

            if (count($aGeneratedEmails) === 1) {
                $oSession->setFlashData(
                    'success',
                    sprintf(
                        'Preview email sent successfully. View it in your browser <a href="%s" style="text-decoration: underline" target="_blank:">here</a>.',
                        reset($aGeneratedEmails)->data->url->viewOnline
                    )
                );
            } else {
                $oSession->setFlashData(
                    'success',
                    sprintf(
                        'Multiple preview emails sent successfully. View them in your browser:',
                        implode(
                            '<br>',
                            array_map(
                                function ($oEmail) {
                                    return $oEmail->data->url->viewOnline;
                                },
                                $aGeneratedEmails
                            )
                        )
                    )
                );
            }

        } catch (EmailerException $e) {
            $oSession->setFlashData(
                'error',
                'An error occurred whislt sending the test email: ' . $e->getMessage()
            );
        }

        redirect('admin/email/templates/index');
    }
}
