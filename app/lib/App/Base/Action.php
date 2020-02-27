<?php

namespace Honeygavi\App\Base;

use AgaviAction;
use AgaviConfig;
use AgaviRequestDataHolder;
use AgaviToolkit;
use AgaviValidationError;
use AgaviValidationIncident;
use AgaviValidator;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\ScopeKeyInterface;
use Honeybee\Common\Util\StringToolkit;
use Honeygavi\Logging\ILogger;
use Honeygavi\Logging\LogTrait;
use Honeybee\Infrastructure\Command\CommandInterface;
use Honeybee\Infrastructure\DataAccess\Query\QueryInterface;
use Honeybee\Projection\ProjectionTypeInterface;
use Honeygavi\Ui\Activity\ActivityMap;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * The Base\Action serves as the base action to all actions implemented inside of honeybee.
 */
abstract class Action extends AgaviAction implements ILogger, ResourceInterface, ScopeKeyInterface
{
    use LogTrait;

    /**
     * Default error handling for method Read (GET Requests)
     *
     * @param AgaviRequestDataHolder $parameters
     * @return array (modulename, viewname)
     */
    public function handleError(AgaviRequestDataHolder $parameters)
    {
        $errors = [];
        foreach ($this->getContainer()->getValidationManager()->getErrorMessages() as $error_message) {
            $errors[] = implode(', ', $error_message['errors']) . ': ' . $error_message['message'];
        }

        $this->setAttribute('errors', $errors);

        return 'Error';
    }

    /**
     * Returns an array to use in an actions handleError or getDefaultViewName
     * methods to forward to the default Error404 success view.
     *
     * @param string message message to display to user
     * @param string title title to display to user
     *
     * @return array of modulename and action/view name
     */
    public function getNotFoundView($message = '', $title = '')
    {
        if (!empty($message)) {
            $this->setAttribute('_404_message', $message);
        }

        if (!empty($title)) {
            $this->setAttribute('_404_title', $title);
        }

        $module_name = AgaviConfig::get('actions.error_404_module');
        $action_name = AgaviConfig::get('actions.error_404_action');

        return [
            $module_name,
            $action_name . '/' . AgaviToolkit::extractShortActionName($action_name) . 'Success'
        ];
    }

    public function isSecure()
    {
        return true;
    }

    public function getResourceId()
    {
        return $this->getScopeKey();
    }

    public function getCredentials()
    {
        return sprintf('%s::%s', $this->getScopeKey(), $this->getContainer()->getRequestMethod());
    }

    public function getScopeKey()
    {
        $class_name_parts = explode('_', static::CLASS);
        $vendor = StringToolkit::asSnakeCase(array_shift($class_name_parts));
        $short_name = implode('.', array_map([StringToolkit::CLASS, 'asSnakeCase' ], $class_name_parts));

        return preg_replace('~_action$~', '', $vendor . '.' . $short_name);
    }

    public function getActivities($resource = null)
    {
        $activities = null;
        if ($resource) {
            $activity_service = $this->getContext()->getServiceLocator()->getActivityService();
            $activities = $activity_service->getActivityMap($resource ?: $this);
        } else {
            $activities = new ActivityMap();
        }

        return $activities;
    }

    /**
     * Returns the aggregate root type for the current action. This depends on the class name.
     *
     * @return AggregateRootTypeInterface
     */
    protected function getAggregateRootType()
    {
        $class_name_parts = explode('_', static::CLASS);
        $vendor = array_shift($class_name_parts);
        $package = array_shift($class_name_parts);
        $entity_type = array_shift($class_name_parts);
        $prefix = sprintf(
            '%s.%s.%s',
            StringToolkit::asSnakeCase($vendor),
            StringToolkit::asSnakeCase($package),
            StringToolkit::asSnakeCase($entity_type)
        );

        return $this->getServiceLocator()->getAggregateRootTypeMap()->getItem($prefix);
    }

    /**
     * Returns the (Standard) projection type for the current action. This depends on the class name.
     *
     * @param string $variant name of projection type variant (defaults to 'Standard')
     *
     * @return ProjectionTypeInterface
     */
    protected function getProjectionType($variant = ProjectionTypeInterface::DEFAULT_VARIANT)
    {
        $class_name_parts = explode('_', static::CLASS);
        $vendor = array_shift($class_name_parts);
        $package = array_shift($class_name_parts);
        $entity_type = array_shift($class_name_parts);
        $variant_prefix = sprintf(
            '%s.%s.%s::projection.%s',
            StringToolkit::asSnakeCase($vendor),
            StringToolkit::asSnakeCase($package),
            StringToolkit::asSnakeCase($entity_type),
            StringToolkit::asSnakeCase($variant)
        );

        return $this->getServiceLocator()->getProjectionTypeMap()->getItem($variant_prefix);
    }

    protected function getServiceLocator()
    {
        return $this->getContext()->getServiceLocator();
    }

    /**
     * Looks up service key in ServiceLocator and returns the found service.
     *
     * @param string $service_key
     *
     * @return object service
     *
     * @throws RuntimeError when service key yields no result
     */
    protected function getService($service_key)
    {
        $service = $this->getServiceLocator()->get($service_key);

        if (!$service) {
            throw new RuntimeError('Unable to find service for key: ' . $service_key);
        }

        return $service;
    }

    /**
     * add a validation error out of the action
     *
     * @param string $argument argument name
     * @param string $message error message
     * @param int $severity
     * @return AgaviValidationIncident the generated error
     */
    protected function addError($argument, $message, $severity = AgaviValidator::ERROR)
    {
        $validation_manager = $this->getContainer()->getValidationManager();
        $incident = new AgaviValidationIncident(null, $severity);
        $incident->addError(new AgaviValidationError($message, null, [ $argument ]));
        $validation_manager->addIncident($incident);

        return $incident;
    }

    /**
     * Posts given command onto the command bus and returns the command.
     *
     * @param CommandInterface $command
     *
     * @return CommandInterface given command
     */
    protected function dispatchCommand(CommandInterface $command)
    {
        $this->getServiceLocator()->getCommandBus()->post($command);

        return $command;
    }

    /**
     * Queries the view store of projection type for (standard) variant.
     *
     * @param QueryInterface $query query to execute on projection type
     * @param string $mapping_finder mapping to use in query service for projection type (uses 'default' when null)
     * @param string $variant name of projection type variant (defaults to 'Standard')
     *
     * @return FinderResultInterface
     */
    protected function query(
        QueryInterface $query,
        $mapping_name = null,
        $variant = ProjectionTypeInterface::DEFAULT_VARIANT
    ) {
        $data_access_service = $this->getServiceLocator()->getDataAccessService();
        $query_service_map = $data_access_service->getQueryServiceMap();

        return $query_service_map->getByProjectionType($this->getProjectionType($variant))->find($query, $mapping_name);
    }

    /**
     *  Set the layout path to extend into the template, if successfully validated
     **/
    protected function setLayoutAttribute($request_data)
    {
        if ($request_data->hasParameter('_layout')) {
            $this->setAttribute('_layout', $request_data->getParameter('_layout'));
            return true;
        }
        return false;
    }
}
