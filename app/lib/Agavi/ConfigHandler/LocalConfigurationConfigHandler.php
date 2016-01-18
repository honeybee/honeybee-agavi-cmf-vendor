<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use AgaviConfig;
use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\ArrayToolkit;
use Symfony\Component\Yaml\Parser;
use Exception;

class LocalConfigurationConfigHandler extends BaseConfigHandler
{
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/local_configuration/1.0';

    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'lcfg');

        $local_settings = [];
        foreach ($document->getConfigurationElements() as $configuration_element) {
            $local_settings_el = $configuration_element->getChild('local_settings');
            foreach ($local_settings_el->get('from_file') as $local_file_element) {
                $local_settings = array_merge($this->parseLocalFileNode($local_file_element), $local_settings);
            }
            foreach ($local_settings_el->get('from_env') as $local_env_element) {
                $env_setting = $this->parseLocalEnvNode($local_env_element);
                $setting_name = $env_setting['name'];
                $env_var_name = isset($env_setting['var']) ? $env_setting['var'] : $setting_name;
                $env_var_value = getenv($env_var_name);
                if ($env_var_value) {
                    $local_settings[$setting_name] = $env_var_value;
                } else if (isset($env_setting['settings']['required'])
                    && $env_setting['settings']['required'] === true
                ) {
                    throw new RuntimeError('Required environment variable "' . $env_var_name . '" has not been set.');
                }
            }
        }

        $configuration_code = '';
        foreach ($local_settings as $key => $value) {
            $configuration_code .= sprintf("AgaviConfig::set('%s', %s);\n", $key, var_export($value, true));
        }

        return $this->generate($configuration_code, $document->documentURI);
    }

    protected function parseLocalFileNode(AgaviXmlConfigDomElement $local_file_element)
    {
        return $this->processLocalFileSetting(
            [
                'type' => $local_file_element->getAttribute('type'),
                'path' => $local_file_element->getChild('path')->getValue(),
                'settings' => $this->parseSettings($local_file_element)
            ]
        );
    }

    protected function parseLocalEnvNode(AgaviXmlConfigDomElement $local_env_element)
    {
        $env_var_info = [
            'name' => $local_env_element->getAttribute('name'),
            'settings' => $this->parseSettings($local_env_element)
        ];

        $var_node = $local_env_element->getChild('var');
        if ($var_node) {
            $env_var_info['var'] = $var_node->getValue();
        }

        return $env_var_info;
    }

    protected function processLocalFileSetting(array $local_setting_info)
    {
        $settings = [];

        $file_path = AgaviConfig::get('core.local_config_dir') . '/' . $local_setting_info['path'];
        if (!is_readable($file_path)) {
            throw new RuntimeError('Unable to read local config file at: ' . $file_path);
        }

        if ($local_setting_info['type'] === 'yaml') {
            $yaml_string = file_get_contents($file_path);
            if (!$yaml_string) {
                throw new Exception('Failed to read local configuration at: ' . $file_path);
            }
            try {
                $yaml_parser = new Parser();
                $settings = $yaml_parser->parse($yaml_string);
            } catch (Exception $parse_error) {
                throw new RuntimeError(
                    'Failed to parse yaml for local config file: ' . $file_path . PHP_EOL .
                    'Error: ' . $parse_error->getMessage()
                );
            }
        } elseif ($local_setting_info['type'] === 'json') {
            $json_string = file_get_contents($file_path);
            if (!$json_string) {
                throw new Exception('Failed to read local configuration at: ' . $file_path);
            }
            $settings = json_decode($json_string, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeError('Failed to parse json from file "' . $file_path . '": ' . json_last_error_msg());
            }
        } else {
            throw new RuntimeError('Only "yaml" or "json" are supported for "type" setting of local configs.');
        }

        if (!isset($local_setting_info['settings']['flatten'])
            || $local_setting_info['settings']['flatten'] === true
        ) {
            $settings = ArrayToolkit::flatten($settings);
        }

        return $settings;
    }
}
