<?php

namespace Honeybee\SystemAccount;

use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\DataAccess\Query\AttributeCriteria;
use Honeybee\Infrastructure\DataAccess\Query\Comparison\Equals;
use Honeybee\Infrastructure\DataAccess\Query\CriteriaList;
use Honeybee\Infrastructure\DataAccess\Query\CriteriaQuery;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeygavi\Security\Auth\AuthResponse;
use Honeygavi\Security\Auth\AuthServiceInterface;
use Honeygavi\Security\Auth\CryptedPasswordHandler;

class StandardAuthService implements AuthServiceInterface
{
    const ACTIVE_STATE = 'active';

    const TYPE_KEY = 'standard-auth';

    protected $config;

    protected $query_service_map;

    protected $password_handler;

    public function __construct(
        ConfigInterface $config,
        QueryServiceMap $query_service_map,
        CryptedPasswordHandler $password_handler
    ) {
        $this->config = $config;
        $this->query_service_map = $query_service_map;
        $this->password_handler = $password_handler;
    }

    public function getTypeKey()
    {
        return static::TYPE_KEY;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function authenticate($username, $password, $options = array()) // @codingStandardsIgnoreEnd
    {
        $query_result = $this->getProjectionQueryService()->find(
            new CriteriaQuery(
                new CriteriaList,
                new CriteriaList([ new AttributeCriteria('username', new Equals($username)) ]),
                new CriteriaList,
                0,
                1
            )
        );

        $user = null;
        if (1 === $query_result->getTotalCount()) {
            $user = $query_result->getFirstResult();
        } else {
            return new AuthResponse(AuthResponse::STATE_UNAUTHORIZED, 'user not found');
        }

        if ($user->getPasswordHash() === '') {
            return new AuthResponse(AuthResponse::STATE_UNAUTHORIZED, 'user password not set');
        }

        /*
        if ($user->getWorkflowState() !== $this->config->get('active_state', self::ACTIVE_STATE)) {
            return new AuthResponse(AuthResponse::STATE_UNAUTHORIZED, 'user inactive');
        }
        */

        if ($this->password_handler->verify($password, $user->getPasswordHash())) {
            return new AuthResponse(
                AuthResponse::STATE_AUTHORIZED,
                "authenticaton success",
                [
                    'login' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'acl_role' => $user->getRole(),
                    'name' => $user->getFirstname() . ' ' . $user->getLastname(),
                    'identifier' => $user->getIdentifier(),
                    'background_images' => $user->getBackgroundImages()
                ]
            );
        }

        return new AuthResponse(AuthResponse::STATE_UNAUTHORIZED, 'authentication failed');
    }

    protected function getProjectionQueryService()
    {
        $query_service_key = $this->config->get(
            'query_service',
            'honeybee.system_account.user::projection.standard::view_store::query_service'
        );

        if (!$this->query_service_map->hasKey($query_service_key)) {
            throw new RuntimeError('Unable to find QueryService for key: ' . $query_service_key);
        }

        return $this->query_service_map->getItem($query_service_key);
    }
}
