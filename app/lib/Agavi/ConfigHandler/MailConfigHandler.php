<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use Honeybee\Common\Error\ConfigError;
use Honeybee\Infrastructure\Mail\Message;
use Honeybee\Infrastructure\Mail\MailServiceInterface;

/**
 * Mail configuration files contain mailer elements that define settings
 * used by the MailService.
 */
class MailConfigHandler extends BaseConfigHandler
{
    /**
     * Name of the mail schema namespace.
     */
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/mail/1.0';

    /**
     * Execute this mail configuration handler.
     *
     * @param AgaviXmlConfigDomDocument $document configuration document
     *
     * @return string data to be written to a cache file
     */
    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'mail');
        $mailers = [];
        $default_mailer_names = [];

        // iterate over configuration nodes and merge settings recursively
        foreach ($document->getConfigurationElements() as $configuration) {
            list($default_mailer_name, $new_mailers) = $this->parseMailers($configuration, $document);
            $mailers = self::mergeSettings($mailers, $new_mailers);
            $default_mailer_names[] = $default_mailer_name;
        }
        $default_mailer_names = array_reverse($default_mailer_names); // most significant name is now on top

        // there must be a valid default mailer section
        $default_mailer_name = array_shift($default_mailer_names);
        if (!isset($mailers[$default_mailer_name])) {
            $mailer_names = array_keys($mailers);
            sort($mailer_names);
            sort($default_mailer_names);

            throw new ConfigError(
                sprintf(
                    'Configuration file "%s" specified a non-existant default mailer "%s". ' .
                    'Available other default mailer names are: "%s". Found mailers are: "%s".',
                    $document->documentURI,
                    $default_mailer_name,
                    implode(', ', $default_mailer_names),
                    implode(', ', $mailer_names)
                )
            );
        }

        // empty settings for the default mailer are suspicious
        if (!isset($mailers[$default_mailer_name]) || !is_array($mailers[$default_mailer_name])) {
            throw new ConfigError(
                sprintf(
                    'Configuration file "%s" specified an empty mail configuration for the default mailer "%s".',
                    $document->documentURI,
                    $default_mailer_name
                )
            );
        }

        // prepare array to return
        $data = [
            MailServiceInterface::DEFAULT_MAILER_NAME => $mailers[$default_mailer_name],
            MailServiceInterface::DEFAULT_MAILERS_KEY => $mailers
        ];

        $config_code = sprintf('return %s;', var_export($data, true));

        return $this->generate($config_code, $document->documentURI);
    }

    /**
     * Returns the mailers and their settings from the given configuration node.
     *
     * @param AgaviXmlConfigDomElement $configuration configuration node to examine
     * @param AgaviXmlConfigDomDocument $document document the node was taken from
     *
     * @return array with two elements - name of the default mailer according to this configuration node and
     *              an array of mailers with their respective settings
     *
     * @throws ConfigError when certain required attributes or nodes are missing
     */
    protected function parseMailers(AgaviXmlConfigDomElement $configuration, AgaviXmlConfigDomDocument $document)
    {

        if (!$configuration->has('mailers')) {
            return array('', array());
        }

        $mailers_element = $configuration->getChild('mailers');

        // we need a default mailer to use
        if (!$mailers_element->hasAttribute('default')) {
            throw new ConfigError(
                sprintf(
                    'Configuration file "%s" must specify a default mailer to use via ' .
                    'the "default" attribute on the "mailers" element.',
                    $document->documentURI
                )
            );
        }

        $mailers = array();
        $default_mailer_name = $mailers_element->getAttribute('default');

        // there may be multiple mailers, each should have a name
        foreach ($mailers_element->getChildren('mailer') as $mailer) {
            $mailer_name = $mailer->hasAttribute('name') ? trim($mailer->getAttribute('name')) : '';
            if (empty($mailer_name)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "name" attribute for a "mailer" element.',
                        $document->documentURI
                    )
                );
            }

            // parse all settings from given settings node
            $settings_node = $mailer->getChild('settings');
            $settings = $settings_node ? $this->parseSettings($settings_node) : array();

            // this setting should contain a valid email address as it overrides all email recipients upon send
            if (isset($settings['override_all_recipients'])) {
                $this->validateEmailValue(
                    $settings['override_all_recipients'],
                    'override_all_recipients',
                    $mailer_name,
                    $document
                );
            }

            // setting should contain a valid email address as it contains addresses confirmation emails are sent to
            if (isset($settings['read_receipt_to'])) {
                $this->validateEmailValue($settings['read_receipt_to'], 'read_receipt_to', $mailer_name, $document);
            }

            // there are two areas of settings that define email addresses & should only contain valid email addresses
            $email_containing_settings = array('address_overrides', 'address_defaults');
            foreach ($email_containing_settings as $setting_name) {
                if (!isset($settings[$setting_name])) {
                    continue;
                }

                // fieldnames are something like 'to', 'from', 'reply_to' etc.
                foreach ($settings[$setting_name] as $fieldname => $value) {
                    $this->validateEmailValue($value, $setting_name, $mailer_name, $document, $fieldname);
                }
            }

            // append validated settings to the current mailer
            $mailers[$mailer_name] = $settings;
        }

        return array($default_mailer_name, $mailers);
    }

    /**
     * @param mixed $value email string or array with emails to check
     * @param string $setting_name
     * @param string $mailer_name
     * @param AgaviXmlConfigDomDocument $document
     * @param string $fieldname fieldname like 'to' etc. if it is a nested setting like 'address_overrides'
     *
     * @return void
     *
     * @throws ConfigError if given value is not a valid email or array of valid emails
     */
    protected function validateEmailValue($value, $setting_name, $mailer_name, $document, $fieldname = null)
    {
        if (is_array($value)) {
            foreach ($value as $email) {
                if (!is_string($email) || empty($email) || !Message::isValidEmail($email)) {
                    throw new ConfigError(
                        sprintf(
                            'Configuration file "%s" specifies an invalid email address for ' .
                            'the "%s" setting %s on mailer "%s": %s',
                            $document->documentURI,
                            $setting_name,
                            empty($fieldname) ? '' : "of the $fieldname field ",
                            $mailer_name,
                            $email
                        )
                    );
                }
            }
        } else {
            if (empty($value) || !Message::isValidEmail($value)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" specifies an invalid email address for ' .
                        'the "%s" setting %s on mailer "%s": %s',
                        $document->documentURI,
                        $setting_name,
                        empty($fieldname) ? '' : "of the $fieldname field ",
                        $mailer_name,
                        $value
                    )
                );
            }
        }
    }
}
