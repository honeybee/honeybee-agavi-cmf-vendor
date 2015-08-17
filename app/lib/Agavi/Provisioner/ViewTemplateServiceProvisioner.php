<?php

namespace Honeybee\FrameworkBinding\Agavi\Provisioner;

use AgaviConfig;
use AgaviConfigCache;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\ServiceDefinitionInterface;
use Honeybee\Ui\ViewTemplate\Part\Cell;
use Honeybee\Ui\ViewTemplate\Part\CellList;
use Honeybee\Ui\ViewTemplate\Part\Field;
use Honeybee\Ui\ViewTemplate\Part\FieldList;
use Honeybee\Ui\ViewTemplate\Part\Group;
use Honeybee\Ui\ViewTemplate\Part\GroupList;
use Honeybee\Ui\ViewTemplate\Part\Panel;
use Honeybee\Ui\ViewTemplate\Part\PanelList;
use Honeybee\Ui\ViewTemplate\Part\Row;
use Honeybee\Ui\ViewTemplate\Part\RowList;
use Honeybee\Ui\ViewTemplate\Part\Tab;
use Honeybee\Ui\ViewTemplate\Part\TabList;
use Honeybee\Ui\ViewTemplate\ViewTemplate;
use Honeybee\Ui\ViewTemplate\ViewTemplateMap;
use Honeybee\Ui\ViewTemplate\ViewTemplateServiceInterface;
use Honeybee\Ui\ViewTemplate\ViewTemplatesContainer;
use Honeybee\Ui\ViewTemplate\ViewTemplatesContainerMap;

class ViewTemplateServiceProvisioner extends AbstractProvisioner
{
    const VIEW_TEMPLATES_CONFIG_NAME = 'view_templates.xml';

    public function build(ServiceDefinitionInterface $service_definition, SettingsInterface $provisioner_settings)
    {
        $view_templates_config = $this->loadViewTemplatesConfig();

        $service = $service_definition->getClass();

        $state = [
            ':config' => $service_definition->getConfig(),
            ':view_templates_container_map' => $this->createViewTemplatesContainerMap($view_templates_config)
            //':view_templates_config' => new ArrayConfig($view_templates_config)
        ];

        $this->di_container
            ->define($service, $state)
            ->share($service)
            ->alias(ViewTemplateServiceInterface::CLASS, $service);
    }

    protected function createViewTemplatesContainerMap($view_templates_config)
    {
        $vtc_map = new ViewTemplatesContainerMap();

        foreach ($view_templates_config as $scope => $container_data) {
            $view_template_map = new ViewTemplateMap();

            foreach ($container_data['view_templates'] as $view_template) {
                $view_template_map->setItem($view_template['name'], $this->buildViewTemplate($view_template));
            }

            $data = [
                'scope' => $scope,
                'view_template_map' => $view_template_map
            ];

            $view_templates_container = new ViewTemplatesContainer($data);

            $vtc_map->setItem($scope, $view_templates_container);
        }

        return $vtc_map;
    }

    protected function buildViewTemplate($template_config)
    {
        $tab_list = new TabList();

        foreach ($template_config['tabs'] as $tab_name => $tab_config) {
            $panel_list = new PanelList();
            foreach ($tab_config['panels'] as $panel_config) {
                $row_list = new RowList();
                foreach ($panel_config['rows'] as $row_config) {
                    $cell_list = new CellList();
                    foreach ($row_config['cells'] as $cell_config) {
                        $group_list = new GroupList();
                        foreach ($cell_config['groups'] as $group_name => $group_config) {
                            $field_list = new FieldList();
                            foreach ($group_config['fields'] as $field_name => $field_data) {
                                $field = new Field($field_name, new ArrayConfig($field_data));
                                $field_list->addItem($field);
                            }
                            $group_css = $group_config['css'] ?: '';
                            $group_list->addItem(new Group($group_name, $field_list, $group_css));
                        }
                        $cell_css = $cell_config['css'] ?: '';
                        $cell_list->addItem(new Cell($group_list, $cell_css));
                    }
                    $row_css = $row_config['css'] ?: '';
                    $row_list->addItem(new Row($cell_list, $row_css));
                }
                $panel_css = $panel_config['css'] ?: '';
                $panel_list->addItem(new Panel($panel_config['name'], $row_list, $panel_css));
            }
            $tab_css = $tab_config['css'] ?: '';
            $tab_list->addItem(new Tab($tab_name, $panel_list, $tab_css));
        }

        return new ViewTemplate($template_config['name'], $tab_list);
    }

    protected function loadViewTemplatesConfig()
    {
        return include AgaviConfigCache::checkConfig(
            AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR . self::VIEW_TEMPLATES_CONFIG_NAME
        );
    }
}
