<?php

namespace Honeybee\Ui\Renderer\Html;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Zend\Permissions\Acl\Resource\GenericResource;

class AclRolesRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        if (StringToolkit::endsWith($view_scope, 'collection')) {
            return $this->output_format->getName() . '/attribute/text-list/as_itemlist_item_cell.twig';
        }

        return $this->output_format->getName() . '/attribute/choice/as_input_roles.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $params['translation_domain_roles'] = $this->getOption('translation_domain_roles', 'application.roles');

        $wanted_roles = $this->getOption('allowed_values', $this->environment->getUser()->getAvailableRoles());
/*
        $allowed_roles = [];
        $user = $this->environment->getUser();
        foreach ($wanted_roles as $role_id) {
            if ($user->isAllowed(null, 'assign-role:'.$role_id)) {
                $allowed_roles[] = $role_id;
            }
        }
        if ($user->isAllowed($this->attribute, 'read')) {
            $allowed_roles = $wanted_roles;
        }
 */

        $params['allowed_roles'] = $wanted_roles;

        return $params;
    }

    protected function getInputTemplateParameters()
    {
        $global_input_parameters = parent::getInputTemplateParameters();

        if (!empty($global_input_parameters['readonly'])) {
            $global_input_parameters['disabled'] = 'disabled';
        }

        return $global_input_parameters;
    }

    protected function getWidgetImplementor()
    {
        return $this->getOption('widget', 'jsb_Honeybee_Core/ui/SelectBox');
    }
}
