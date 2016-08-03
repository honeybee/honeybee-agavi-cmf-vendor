<?php

namespace Honeybee\FrameworkBinding\Agavi\User;

use AgaviConfig;
use AgaviContext;
use AgaviConfigCache;
use AgaviSecurityUser;
use Exception;
use Honeybee\Infrastructure\Security\Acl\AclService;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * The AclSecurityUser is aware of the system's available activities and of the specific permissions,
 * which are available for controlling various aspects on how activities may be accessed.
 */
class AclSecurityUser extends AgaviSecurityUser implements RoleInterface
{
    const ALL_USERS_ID = null;

    protected $acl;

    protected $roles_configuration;

    protected $raw_referer = '';

    protected $raw_user_agent = '';

    public function initialize(AgaviContext $context, array $parameters = array())
    {
        parent::initialize($context, $parameters);

        $this->roles_configuration = include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . '/access_control.xml'
        );

        $this->activity_service = $context->getServiceLocator()->getActivityService();

        $this->acl_service = $context->getServiceLocator()->getAclService();
        $this->acl = $this->acl_service->getAclForRole($this->getRoleId());

        $this->hydrateHeaderInformation();
    }

    public function getActivities($resource, $scope)
    {
        return $this->activity_service->getActivityMap($resource, $scope);
    }

    public function getAvailableRoles()
    {
        $honeybee_standard_roles = [ AclService::ROLE_ADMIN, AclService::ROLE_NON_PRIV ];
        $configured_roles = array_keys(($this->roles_configuration['roles']));

        return array_merge($honeybee_standard_roles, $configured_roles);
    }

    public function getAcl()
    {
        return $this->acl;
    }

    public function isAllowed($resource = null, $operation = null)
    {
        return $this->getAcl()->isAllowed($this, $resource, $operation);
    }

    public function hasRole($role)
    {
        // could be our role directly, could be an ancestor, so check both
        return $this->getRoleId() == $role || $this->getAcl()->inheritsRole($this->getRoleId(), $role);
    }

    public function getRoleId()
    {
        if ($this->isAuthenticated() && $this->hasAttribute('acl_role')) {
            return $this->getAttribute('acl_role');
        }

        return $this->getParameter('default_acl_role', AclService::ROLE_NON_PRIV);
    }

    public function hasCredential($credential)
    {
        try {
            if ($credential instanceof ResourceInterface) {
                return $this->isAllowed($credential);
            }
            if (!is_scalar($credential)) {
                return false;
            }
            return $this->isAllowed(null, $credential);
        } catch (Exception $e) {
            // @todo log error message
            return false;
        }
    }

    /**
     * @return string UNTRUSTED USER-AGENT header value (prior validation!)
     */
    public function getRawUserAgent()
    {
        return $this->raw_user_agent;
    }

    /**
     * @return string UNTRUSTED REFERER header value (prior validation!)
     */
    public function getRawReferer()
    {
        return $this->raw_referer;
    }

    public function setAuthenticated($authenticated)
    {
        parent::setAuthenticated($authenticated);

        $this->acl = $this->acl_service->getAclForRole($this->getRoleId());
    }

    protected function hydrateHeaderInformation()
    {
        if ($this->getContext()->getName() == 'console') {
            $this->raw_user_agent = 'console';
            $this->raw_referer = '';
        } else {
            $request = $this->getContext()->getRequest();
            $this->raw_user_agent = $request->getRequestData()->getHeader('User-Agent', '');
            $this->raw_referer = $request->getRequestData()->getHeader('Referer', '');
        }
    }
}
