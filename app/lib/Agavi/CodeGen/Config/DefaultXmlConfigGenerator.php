<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

use AgaviConfig;
use DOMDocument;

class DefaultXmlConfigGenerator implements ConfigGeneratorInterface
{
    public function generate($name, array $files_to_include)
    {
        if (!empty($files_to_include)) {
            $document = $this->createDocument($name);
            $root = $document->documentElement;

            foreach ($files_to_include as $configFile) {
                $include = $this->createInclude($document, $configFile);
                $root->appendChild($include);
            }

            $this->writeConfigFile($document, $name);
        }

        return $files_to_include;
    }

    protected function createDocument($name)
    {
        $document = new DOMDocument('1.0', 'utf-8');
        $root = $document->createElementNs(
            'http://agavi.org/agavi/config/global/envelope/1.0',
            'ae:configurations'
        );
        $root->setAttribute(
            'xmlns',
            sprintf('http://agavi.org/agavi/config/parts/%s/1.0', $name)
        );
        $root->setAttribute('xmlns:xi', 'http://www.w3.org/2001/XInclude');
        $document->appendChild($root);

        return $document;
    }

    protected function createInclude(DOMDocument $document, $abs_config_file_path)
    {
        $include = $document->createElement('xi:include');
        $relative_href = str_replace(AgaviConfig::get('core.app_dir'), '../..', $abs_config_file_path);
        $include->setAttribute('href', $relative_href);
        $include->setAttribute(
            'xpointer',
            'xmlns(ae=http://agavi.org/agavi/config/global/envelope/1.0) xpointer(/ae:configurations/*)'
        );

        return $include;
    }

    protected function writeConfigFile(DOMDocument $document, $name)
    {
        $config_include_dir = AgaviConfig::get('core.config_dir');
        $config_include_dir .= DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
        $output_path = $config_include_dir.$name.'.xml';

        $document->formatOutput = true;
        $document->save($output_path);
    }
}
