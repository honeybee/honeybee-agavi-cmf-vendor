<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use Honeybee\Common\Error\ConfigError;
use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;

/**
 * Filesystems configuration files contain filesystem elements that define
 * a scheme name and a connection name (from connections.xml). The connector
 * returns a Filesystem instance to mount for the specified scheme.
 */
class FilesystemsConfigHandler extends BaseConfigHandler
{
    /**
     * Name of the view settings schema namespace.
     */
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/filesystems/1.0';

    /**
     * Execute this configuration handler.
     *
     * @param AgaviXmlConfigDomDocument $document configuration document
     *
     * @return string data to be written to a cache file
     */
    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'filesystems');

        $filesystems = [];

        // iterate over configuration nodes and merge settings recursively
        foreach ($document->getConfigurationElements() as $configuration) {
            $new_filesystems = $this->parseFilesystems($configuration, $document);
            $filesystems = self::mergeSettings($filesystems, $new_filesystems);
        }

        $config_code = sprintf('return %s;', var_export($filesystems, true));

        return $this->generate($config_code, $document->documentURI);
    }

    /**
     * Returns the filesystems from the given configuration node.
     *
     * @param AgaviXmlConfigDomElement $configuration configuration node to examine
     * @param AgaviXmlConfigDomDocument $document document the node was taken from
     *
     * @return array of filesystems
     *
     * @throws ConfigError when certain required attributes or nodes are missing
     */
    protected function parseFilesystems(AgaviXmlConfigDomElement $configuration, AgaviXmlConfigDomDocument $document)
    {
        if (!$configuration->has('filesystems')) {
            return [];
        }

        $filesystems_element = $configuration->getChild('filesystems');

        $filesystems = [];

        // there may be multiple filesystems specified with scheme and connection name
        foreach ($filesystems_element->getChildren('filesystem') as $filesystem) {
            $scheme = $filesystem->hasAttribute('scheme') ? trim($filesystem->getAttribute('scheme')) : '';
            if (empty($scheme)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "scheme" attribute for a "filesystem" element.',
                        $document->documentURI
                    )
                );
            }

            $connection = $filesystem->hasAttribute('connection') ? trim($filesystem->getAttribute('connection')) : '';
            if (empty($connection)) {
                throw new ConfigError(
                    sprintf(
                        'Configuration file "%s" must specify a "connection" attribute for a "filesystem" element.',
                        $document->documentURI
                    )
                );
            }

            $filesystems[$scheme] = $connection;
        }

        return $filesystems;
    }
}
