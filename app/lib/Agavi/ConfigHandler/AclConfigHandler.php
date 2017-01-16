<?php

namespace Honeybee\FrameworkBinding\Agavi\ConfigHandler;

use Trellis\Common\Error\RuntimeException;
use AgaviXmlConfigDomDocument;
use AgaviXmlConfigDomElement;
use AgaviXmlConfigHandler;

/**
 * AclConfigHandler parses configuration files that follow the honeybee access_control markup.
 */
class AclConfigHandler extends AgaviXmlConfigHandler
{
    /**
     * Holds the name of the access_control document schema namespace.
     */
    const XML_NAMESPACE = 'http://berlinonline.de/schemas/honeybee/config/access_control/1.0';

    /**
     * An assoc array that maps external roles/groups/whatever to local domain roles.
     *
     * @var array
     */
    protected $external_roles;

    /**
     * Execute this configuration handler.
     *
     * @param      string An absolute filesystem path to a configuration file.
     * @param      string An optional context in which we are currently running.
     *
     * @return     string Data to be written to a cache file.
     *
     * @throws     <b>AgaviUnreadableException</b> If a requested configuration
     *                                             file does not exist or is not
     *                                             readable.
     * @throws     <b>AgaviParseException</b> If a requested configuration file is
     *                                        improperly formatted.
     */
    public function execute(AgaviXmlConfigDomDocument $document)
    {
        $this->external_roles = array();
        $document->setDefaultNamespace(self::XML_NAMESPACE, 'acl');
        $domain_roles = array();
        foreach ($document->getConfigurationElements() as $configuration_node) {
            if ($roles_node = $configuration_node->getChild('roles')) {
                $domain_roles = array_merge($domain_roles, $this->parseRoles($roles_node));
            }
        }

        $config_data = array('roles' => $domain_roles, 'external_roles' => $this->external_roles);
        $config_code = sprintf('return %s;', var_export($config_data, true));

// error_log(var_export($config_data, true));
        return $this->generate($config_code, $document->documentURI);
    }

    /**
     * Parse the given roles node and return the corresponding array representation.
     *
     * @param AgaviXmlConfigDomElement $roles_element
     *
     * @return array
     */
    protected function parseRoles(AgaviXmlConfigDomElement $roles_element)
    {
        $parsed_roles = array();
        foreach ($roles_element->get('role') as $role_element) {
            $role = $role_element->getAttribute('name');

            // parse the members ...
            $members = array();
            $members_element = $role_element->getChild('members');
            if ($members_element) {
                $members = $this->parseRoleMembers($members_element);
                // Register role mappings (will allow to map for example ldap::user to news.editor).
                foreach ($members as $member) {
                    $external_role = sprintf('%s::%s', $member['type'], $member['name']);
                    $this->addExternalRole($external_role, $role);
                }
            }

            // ..., parse the acl ...
            $acl = array('grant' => array(), 'deny' => array());
            $acl_element = $role_element->getChild('acl');
            if ($acl_element) {
                $acl = $this->parseRoleAcl($acl_element);
            }
            // ... then bring them together to define the role.
            $parsed_roles[$role] = array(
                'description' => $role_element->getChild('description')->nodeValue,
                'members' => $members,
                'acl' => $acl,
                'parent' => $role_element->getAttribute('parent', null)
            );
        }

        return $parsed_roles;
    }

    /**
     * Register a mapping from the given external role to the given domain role.
     *
     * @param string $external_role
     * @param string $domain_role
     */
    protected function addExternalRole($external_role, $domain_role)
    {
        $this->external_roles[$external_role] = $domain_role;
    }

    /**
     * Parse the given members node and return the corresponding array representation.
     *
     * @param AgaviXmlConfigDomElement $members_element
     *
     * @return array
     */
    protected function parseRoleMembers(AgaviXmlConfigDomElement $members_element)
    {
        $members = array();
        foreach ($members_element->get('member') as $memberNode) {
            $members[] = array(
                'type' => $memberNode->getAttribute('type'),
                'name' => $memberNode->nodeValue
            );
        }

        return $members;
    }

    /**
     * Parse the given acl node and return the corresponding array representation.
     *
     * @param AgaviXmlConfigDomElement $acl_element
     *
     * @return array
     */
    protected function parseRoleAcl(AgaviXmlConfigDomElement $acl_element)
    {
        $acl = array();
        foreach ($acl_element->getChildren('permissions', null, false) as $permissions_node) {
            $scope = $permissions_node->getAttribute('scope');
            $acl[$scope] = array();
            foreach ($permissions_node->getIterator() as $credential_node) {
                $credential = $this->parseCredential($credential_node);
                $credential['scope'] = $scope;
                if ($scope === 'honeybee.system_account.user.create' || $scope === 'popula.ems.misc.dashboard') {
                    error_log(__METHOD__ . ' - ' . var_export($credential, true));
                }
                $acl[$scope][] = $credential;
            }
        }

        return $acl;
    }

    protected function parseCredential(AgaviXmlConfigDomElement $credential_node)
    {
        static $supported_types = array('activity', 'field', 'plugin', 'method');

        $credential_string = $credential_node->nodeValue;
        $type_parts = preg_split('/::/', $credential_string, 2);
        $credential_type = trim(array_shift($type_parts));
        $access_type = $credential_node->nodeName;
        $expression = null;
        $operation = null;

        if (in_array($credential_type, $supported_types)) {
            $expression_parts = preg_split('/\s+if\s+/', $type_parts[0], 2);
            $expression = count($expression_parts) > 1 ? trim($expression_parts[1]) : null;
            $operation = trim($expression_parts[0]);
        } elseif ('*' !== $credential_type) {
            throw new RuntimeException("Unsupported credential type: " . $credential_type);
        }
        return array(
            'type' => $credential_type,
            'access' => $access_type,
            'operation' => $operation,
            'expression' => $expression
        );
    }
}
