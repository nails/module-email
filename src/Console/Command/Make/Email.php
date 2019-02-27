<?php

namespace Nails\Email\Console\Command\Make;

use Nails\Console\Command\BaseMaker;
use Nails\Console\Exception\ConsoleException;
use Nails\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Email extends BaseMaker
{
    const SERVICE_TOKEN     = 'FACTORIES';
    const RESOURCE_PATH     = __DIR__ . '/../../../../resources/console/';
    const APP_PATH          = NAILS_APP_PATH . 'src/Factory/Email/';
    const TEMPLATE_PATH     = NAILS_APP_PATH . 'modules/email/views/';
    const EMAIL_CONFIG_PATH = NAILS_APP_PATH . 'application/config/email_types.php';

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('make:email')
            ->setDescription('Creates a new email')
            ->addArgument(
                'emailName',
                InputArgument::OPTIONAL,
                'Define the name of the email to create'
            );
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param  InputInterface  $oInput  The Input Interface provided by Symfony
     * @param  OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        try {

            $this
                ->validateServiceFile()
                ->createPath(self::APP_PATH)
                ->createEmail();

        } catch (ConsoleException $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                [$e->getMessage()]
            );
        }

        // --------------------------------------------------------------------------

        //  Cleaning up
        $oOutput->writeln('');
        $oOutput->writeln('<comment>Cleaning up...</comment>');

        // --------------------------------------------------------------------------

        //  And we're done
        $oOutput->writeln('');
        $oOutput->writeln('Complete!');

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Create the Service
     *
     * @throws ConsoleException
     * @return $this
     */
    private function createEmail(): self
    {
        $aFields  = $this->getArguments();
        $aCreated = [];

        try {

            $aToCreate = [];
            $aEmails   = array_filter(
                array_map(function ($sEmail) {
                    return implode('/', array_map('ucfirst', explode('/', ucfirst(trim($sEmail)))));
                }, explode(',', $aFields['EMAIL_NAME']))
            );

            sort($aEmails);

            foreach ($aEmails as $sEmail) {

                $sEmail = ucwords($sEmail);
                $sEmail = str_replace(' ', '', $sEmail);

                $aEmailBits = explode('/', $sEmail);
                $aEmailBits = array_map('ucfirst', $aEmailBits);

                $sNamespace       = $this->generateNamespace($aEmailBits);
                $sClassName       = $this->generateClassName($aEmailBits);
                $sClassNameFull   = $sNamespace . '\\' . $sClassName;
                $sClassNameNormal = str_replace('AppFactory', '', str_replace('\\', '', $sClassNameFull));
                $sFilePath        = $this->generateFilePath($aEmailBits);
                $sEmailKey        = $this->generateEmailKey($aEmailBits);
                $sTemplateDir     = $this->generateTemplateDir($aEmailBits);
                $sTemplateHtml    = $this->generateTemplate('HTML', $aEmailBits);
                $sTemplateText    = $this->generateTemplate('TEXT', $aEmailBits);

                //  Test it does not already exist
                if (file_exists($sFilePath)) {
                    throw new ConsoleException(
                        'An email at "' . $sFilePath . '" already exists'
                    );
                }
                try {
                    $oTest = Factory::factory($sClassNameNormal, 'app');
                    throw new ConsoleException(
                        'An email by "' . $sClassNameNormal . '" is already defined'
                    );
                } catch (\Exception $e) {
                    //  No exception? No problem!
                }

                $aToCreate[] = [
                    'NAMESPACE'             => $sNamespace,
                    'CLASS_NAME'            => $sClassName,
                    'CLASS_NAME_FULL'       => $sClassNameFull,
                    'CLASS_NAME_NORMALISED' => $sClassNameNormal,
                    'FILE_PATH'             => $sFilePath,
                    'DIRECTORY'             => dirname($sFilePath) . DIRECTORY_SEPARATOR,
                    'EMAIL_KEY'             => $sEmailKey,
                    'TEMPLATE_DIR'          => $sTemplateDir,
                    'TEMPLATE_HTML'         => $sTemplateHtml,
                    'TEMPLATE_TEXT'         => $sTemplateText,
                ];
            }

            $this->oOutput->writeln('The following email(s) will be created:');
            foreach ($aToCreate as $aConfig) {
                $this->oOutput->writeln('');
                $this->oOutput->writeln('Class:       <info>' . $aConfig['CLASS_NAME_FULL'] . '</info>');
                $this->oOutput->writeln('Key:         <info>' . $aConfig['CLASS_NAME_NORMALISED'] . '</info>');
                $this->oOutput->writeln('Path:        <info>' . $aConfig['FILE_PATH'] . '</info>');
                $this->oOutput->writeln('View (HTML): <info>' . $aConfig['TEMPLATE_HTML'] . '</info>');
                $this->oOutput->writeln('View (TEXT): <info>' . $aConfig['TEMPLATE_TEXT'] . '</info>');
            }
            $this->oOutput->writeln('');

            if ($this->confirm('Continue?', true)) {

                //  Generate emails
                $aServiceDefinitions = [];
                foreach ($aToCreate as $aConfig) {
                    $this->oOutput->writeln('');
                    $this->oOutput->write('Creating email <comment>' . $aConfig['CLASS_NAME_FULL'] . '</comment>... ');
                    $this->createPath($aConfig['DIRECTORY']);
                    $this->createFile(
                        $aConfig['FILE_PATH'],
                        $this->getResource('template/email.php', $aConfig)
                    );
                    $aCreated[] = $aConfig['FILE_PATH'];
                    $this->oOutput->writeln('<info>done!</info>');

                    //  Generate templates
                    $this
                        ->createPath($aConfig['TEMPLATE_DIR'])
                        ->createFile($aConfig['TEMPLATE_HTML']);

                    //  Generate the service definition
                    $aDefinition           = [
                        str_repeat(' ', $this->iServicesIndent) . '\'' . $aConfig['CLASS_NAME_NORMALISED'] . '\' => function () {',
                        str_repeat(' ', $this->iServicesIndent) . '    return new ' . $aConfig['CLASS_NAME_FULL'] . '();',
                        str_repeat(' ', $this->iServicesIndent) . '},',
                    ];
                    $aServiceDefinitions[] = implode("\n", $aDefinition);

                    //  Add to the email config
                    $this->addEmailConfig($aConfig);
                }

                //  Add services to the app's services array
                $this->oOutput->writeln('');
                $this->oOutput->write('Adding email(s) to app services... ');
                $this->writeServiceFile($aServiceDefinitions);
                $this->oOutput->writeln('<info>done!</info>');
            }

        } catch (ConsoleException $e) {
            $this->oOutput->writeln('<error>failed!</error>');
            //  Clean up created services
            if (!empty($aCreated)) {
                $this->oOutput->writeln('<error>Cleaning up - removing newly created files</error>');
                foreach ($aCreated as $sPath) {
                    @unlink($sPath);
                }
            }
            throw new ConsoleException($e->getMessage());
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the class name
     *
     * @param array $aEmailBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateClassName(array $aEmailBits): string
    {
        return array_pop($aEmailBits);
    }

    // --------------------------------------------------------------------------


    /**
     * Generate the class namespace
     *
     * @param array $aEmailBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateNamespace(array $aEmailBits): string
    {
        array_pop($aEmailBits);
        return implode('\\', array_merge(['App', 'Factory', 'Email'], $aEmailBits));
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the class file path
     *
     * @param array $aEmailBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateFilePath(array $aEmailBits): string
    {
        $sClassName = array_pop($aEmailBits);
        return implode(
            DIRECTORY_SEPARATOR,
            array_map(
                function ($sItem) {
                    return rtrim($sItem, DIRECTORY_SEPARATOR);
                },
                array_merge(
                    [static::APP_PATH],
                    $aEmailBits,
                    [$sClassName . '.php']
                )
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the email key
     *
     * @param array $aEmailBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateEmailKey(array $aEmailBits): string
    {
        return implode(
            '_',
            array_map(
                function ($sItem) {
                    return strtolower($sItem);
                },
                $aEmailBits
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the template directory
     *
     * @param array $aEmailBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateTemplateDir(array $aEmailBits): string
    {
        $sClassName = array_pop($aEmailBits);
        return implode(
            DIRECTORY_SEPARATOR,
            array_map(
                function ($sItem) {
                    return rtrim($sItem, DIRECTORY_SEPARATOR);
                },
                array_merge(
                    [static::TEMPLATE_PATH],
                    array_map('strtolower', $aEmailBits),
                    [DIRECTORY_SEPARATOR]
                )
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the template directory
     *
     * @param string $sType      The type of template to generate
     * @param array  $aEmailBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateTemplate(string $sType, array $aEmailBits): string
    {
        $sClassName = end($aEmailBits);
        if ($sType === 'HTML') {
            return $this->generateTemplateDir($aEmailBits) . strtolower($sClassName) . '.php';
        } else {
            return $this->generateTemplateDir($aEmailBits) . strtolower($sClassName) . '_plaintext.php';
        }
    }

    // --------------------------------------------------------------------------

    protected function addEmailConfig(array $aConfig)
    {
        $aTypes = [
            $aConfig['EMAIL_KEY'] => (object) [
                'slug'            => $aConfig['EMAIL_KEY'],
                'name'            => str_replace('\\', ' ', str_replace('App\\Factory\\Email\\', '', $aConfig['CLASS_NAME_FULL'])),
                'description'     => '@todo - write the description for the ' . $aConfig['CLASS_NAME_NORMALISED'] . ' email',
                'template_header' => '',
                'template_body'   => 'email/' . rtrim(str_replace(static::TEMPLATE_PATH, '', $aConfig['TEMPLATE_HTML']), '.php'),
                'template_footer' => '',
                'default_subject' => '@todo - write the subject for the ' . $aConfig['CLASS_NAME_NORMALISED'] . ' email',
            ],
        ];

        $oEmailer = Factory::service('Emailer', 'nails/module-email');
        $oNow     = Factory::factory('DateTime');

        $oEmailer::loadTypes(static::EMAIL_CONFIG_PATH, $aTypes);

        ksort($aTypes);

        $aTypes = array_map(
            function ($oType) {
                if ($oType->template_header == 'email/structure/header') {
                    $oType->template_header = '';
                }
                if ($oType->template_footer == 'email/structure/footer') {
                    $oType->template_footer = '';
                }
                return $oType;
            },
            $aTypes
        );

        $aFile = [
            '<?php',
            '/**',
            ' * This config file defines all the email types for this app',
            ' * Add new emails using the Nails CLI tool: nails make:email',
            ' *',
            ' * Generated: ' . $oNow->format('Y-m-d H:i:s'),
            ' **/',
            '',
            '$config[\'email_types\'] = [',
        ];

        foreach ($aTypes as $oType) {
            $aFile[] = '    (object) [';
            $aFile[] = "        'slug'            => '" . str_replace("'", "\'", $oType->slug) . "',";
            $aFile[] = "        'name'            => '" . str_replace("'", "\'", $oType->name) . "',";
            $aFile[] = "        'description'     => '" . str_replace("'", "\'", $oType->description) . "',";
            $aFile[] = "        'template_header' => '" . str_replace("'", "\'", $oType->template_header) . "',";
            $aFile[] = "        'template_body'   => '" . str_replace("'", "\'", $oType->template_body) . "',";
            $aFile[] = "        'template_footer' => '" . str_replace("'", "\'", $oType->template_footer) . "',";
            $aFile[] = "        'default_subject' => '" . str_replace("'", "\'", $oType->default_subject) . "',";
            $aFile[] = '    ],';
        }

        $aFile[] = '];';
        $aFile[] = '';

        file_put_contents(static::EMAIL_CONFIG_PATH, implode("\n", $aFile));
    }
}
