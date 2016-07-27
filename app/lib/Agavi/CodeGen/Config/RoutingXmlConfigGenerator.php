<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

use AgaviConfig;
use DOMDocument;
use DOMElement;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Symfony\Component\Finder\Finder;

class RoutingXmlConfigGenerator extends DefaultXmlConfigGenerator
{
    public function generate($name, array $files_to_include)
    {
        $document = $this->createDocument($name);
        $root = $document->documentElement;

        $web_config = $document->createElement('ae:configuration');
        $web_config->setAttribute('context', 'web');
        $root->appendChild($web_config);

        $console_config = $document->createElement('ae:configuration');
        $console_config->setAttribute('context', 'console');
        $root->appendChild($console_config);

        $document->appendChild($root);

        $web_routes_node = $document->createElement('routes');
        $web_config->appendChild($web_routes_node);

        $console_routes_node = $document->createElement('routes');
        $console_config->appendChild($console_routes_node);

        foreach ($files_to_include['standard'] as $routing_file) {
            $web_routes_node->appendChild(
                $this->createStandardWebRouting($document, $routing_file)
            );
            $console_routes_node->appendChild(
                $this->createStandardConsoleRouting($document, $routing_file)
            );
        }

        foreach ($files_to_include['honeybee_modules'] as $honeybee_module) {
            $this->appendHoneybeeWebRouting($document, $web_routes_node, $honeybee_module);
            $this->appendHoneybeeConsoleRouting($document, $console_routes_node, $honeybee_module);
        }

        $this->writeConfigFile($document, $name);

        // @todo include routing files that where generated for entities/honeybee-modules
        return $files_to_include['standard'];
    }

    protected function createStandardConsoleRouting(DOMDocument $document, $config_file)
    {
        $module_name = $this->extractModuleNameFromPath($config_file);
        $route_pattern = sprintf('^%s.', str_replace('_', '.', strtolower($module_name)));
        $route_name = strtolower(str_replace('_', '.', strtolower($module_name)));

        $module_route = $document->createElement('route');
        $module_route->setAttribute('name', $route_name);
        $module_route->setAttribute('pattern', $route_pattern);
        $module_route->setAttribute('module', $module_name);

        $xi_include = $document->createElement('xi:include');
        $relative_href = str_replace(AgaviConfig::get('core.app_dir'), '../..', $config_file);
        $xi_include->setAttribute('href', $relative_href);
        $xi_include->setAttribute(
            'xpointer',
            "xmlns(ae=http://agavi.org/agavi/config/global/envelope/1.0) " .
            "xmlns(r=http://agavi.org/agavi/config/parts/routing/1.0) " .
            "xpointer(/ae:configurations/ae:configuration[@context='console']/*)"
        );

        $routes_node = $document->createElement('routes');
        $routes_node->appendChild($xi_include);
        $module_route->appendChild($routes_node);

        return $module_route;
    }

    protected function createStandardWebRouting(DOMDocument $document, $config_file)
    {
        $module_name = $this->extractModuleNameFromPath($config_file);
        $route_pattern = '^/' . str_replace('_', '-', strtolower($module_name)) . '/';
        $route_name = str_replace('_', '.', strtolower($module_name));

        $module_route = $document->createElement('route');
        $module_route->setAttribute('name', $route_name);
        $module_route->setAttribute('pattern', $route_pattern);
        $module_route->setAttribute('module', $module_name);

        $xi_include = $document->createElement('xi:include');
        $relative_href = str_replace(AgaviConfig::get('core.app_dir'), '../..', $config_file);
        $xi_include->setAttribute('href', $relative_href);
        $xi_include->setAttribute(
            'xpointer',
            "xmlns(ae=http://agavi.org/agavi/config/global/envelope/1.0) " .
            "xmlns(r=http://agavi.org/agavi/config/parts/routing/1.0) " .
            "xpointer(//ae:configuration[@context='web']/*)"
        );

        $routes_node = $document->createElement('routes');
        $routes_node->appendChild($xi_include);
        $module_route->appendChild($routes_node);

        return $module_route;
    }

    protected function appendHoneybeeWebRouting(DOMDocument $document, DOMElement $parent_node, $honeybee_module)
    {
        $meta_data = $this->getModuleMetaData($honeybee_module);

        if (!$meta_data) {
            throw new RuntimeError('Missing meta-data file for suspected honeybee-module ' . $honeybee_module);
        }

        $vendor_prefix = strtolower($meta_data['vendor']);
        $package_prefix = StringToolkit::asSnakeCase($meta_data['package']);
        $module_config_dir = sprintf('%s/%s/config', AgaviConfig::get('core.module_dir'), $honeybee_module);
        $module_routing_file = $module_config_dir . '/routing.xml';

        $routing_finder = Finder::create()->files()->name('routing.xml')->sortByName()->in($module_config_dir);
        foreach ($routing_finder as $routing_file) {
            if ($routing_file->getPathname() === $module_routing_file) {
                continue; // exclude app/modules/[module_name]/routing.xml
            }
            $resource_name = basename($routing_file->getPath());
            $resource_prefix = StringToolkit::asSnakeCase($resource_name);

            $route_pattern = sprintf('^/({module:%s-%s-%s})/', $vendor_prefix, $package_prefix, $resource_prefix);
            $route_name = sprintf('%s.%s.%s', $vendor_prefix, $package_prefix, $resource_prefix);
            $entity_type_route = $document->createElement('route');
            $entity_type_route->setAttribute('name', $route_name);
            $entity_type_route->setAttribute('pattern', $route_pattern);
            $entity_type_route->setAttribute('module', $honeybee_module);
            $entity_type_route->setAttribute('action', $resource_name);
            $xi_include = $document->createElement('xi:include');
            $relative_href = str_replace(AgaviConfig::get('core.app_dir'), '../..', $routing_file->getPathname());
            $xi_include->setAttribute('href', $relative_href);
            $xi_include->setAttribute(
                'xpointer',
                "xmlns(ae=http://agavi.org/agavi/config/global/envelope/1.0) " .
                "xmlns(r=http://agavi.org/agavi/config/parts/routing/1.0) " .
                "xpointer(//ae:configuration[@context='web']/*)"
            );
            $routes_node = $document->createElement('routes');
            $routes_node->appendChild($xi_include);
            $entity_type_route->appendChild($routes_node);
            $parent_node->appendChild($entity_type_route);
        }

        $route_pattern = sprintf('^/({module:%s-%s})/', $vendor_prefix, $package_prefix);
        $route_name = sprintf('%s.%s', $vendor_prefix, $package_prefix);
        $package_route = $document->createElement('route');
        $package_route->setAttribute('name', $route_name);
        $package_route->setAttribute('pattern', $route_pattern);
        $package_route->setAttribute('module', $honeybee_module);
        $xi_include = $document->createElement('xi:include');
        $relative_href = str_replace(AgaviConfig::get('core.app_dir'), '../..', $module_routing_file);
        $xi_include->setAttribute('href', $relative_href);
        $xi_include->setAttribute(
            'xpointer',
            "xmlns(ae=http://agavi.org/agavi/config/global/envelope/1.0) " .
            "xmlns(r=http://agavi.org/agavi/config/parts/routing/1.0) " .
            "xpointer(//ae:configuration[@context='web']/*)"
        );
        $routes_node = $document->createElement('routes');
        $routes_node->appendChild($xi_include);
        $package_route->appendChild($routes_node);
        $parent_node->appendChild($package_route);
    }

    protected function appendHoneybeeConsoleRouting(DOMDocument $document, DOMElement $parent_node, $honeybee_module)
    {
        $meta_data = $this->getModuleMetaData($honeybee_module);

        if (!$meta_data) {
            throw new RuntimeError('Missing meta-data file for suspected honeybee-module ' . $honeybee_module);
        }

        $vendor_prefix = strtolower($meta_data['vendor']);
        $package_prefix = StringToolkit::asSnakeCase($meta_data['package']);
        $module_config_dir = sprintf('%s/%s/config', AgaviConfig::get('core.module_dir'), $honeybee_module);
        $module_routing_file = $module_config_dir . '/routing.xml';

        $routing_finder = new Finder();
        $routing_finder->files()->name('routing.xml')->sortByName()->in($module_config_dir);
        foreach ($routing_finder as $routing_file) {
            if ($routing_file->getPathname() === $module_routing_file) {
                continue; // exclude app/modules/[module_name]/routing.xml
            }
            $resource_name = basename($routing_file->getPath());
            $resource_prefix = StringToolkit::asSnakeCase($resource_name);

            $route_pattern = sprintf('^%s.%s.%s.', $vendor_prefix, $package_prefix, $resource_prefix);
            $route_name = sprintf('%s.%s.%s', $vendor_prefix, $package_prefix, $resource_prefix);
            $entity_type_route = $document->createElement('route');
            $entity_type_route->setAttribute('name', $route_name);
            $entity_type_route->setAttribute('pattern', $route_pattern);
            $entity_type_route->setAttribute('module', $honeybee_module);
            $entity_type_route->setAttribute('action', $resource_name);
            $xi_include = $document->createElement('xi:include');
            $relative_href = str_replace(AgaviConfig::get('core.app_dir'), '../..', $routing_file->getPathname());
            $xi_include->setAttribute('href', $relative_href);
            $xi_include->setAttribute(
                'xpointer',
                "xmlns(ae=http://agavi.org/agavi/config/global/envelope/1.0) " .
                "xmlns(r=http://agavi.org/agavi/config/parts/routing/1.0) " .
                "xpointer(//ae:configuration[@context='console']/*)"
            );
            $routes_node = $document->createElement('routes');
            $routes_node->appendChild($xi_include);
            $entity_type_route->appendChild($routes_node);
            $parent_node->appendChild($entity_type_route);
        }

        $route_pattern = sprintf('^%s.%s', $vendor_prefix, $package_prefix);
        $route_name = sprintf('%s.%s', $vendor_prefix, $package_prefix);
        $package_route = $document->createElement('route');
        $package_route->setAttribute('name', $route_name);
        $package_route->setAttribute('pattern', $route_pattern);
        $package_route->setAttribute('module', $honeybee_module);
        $xi_include = $document->createElement('xi:include');
        $relative_href = str_replace(AgaviConfig::get('core.app_dir'), '../..', $module_routing_file);
        $xi_include->setAttribute('href', $relative_href);
        $xi_include->setAttribute(
            'xpointer',
            "xmlns(ae=http://agavi.org/agavi/config/global/envelope/1.0) " .
            "xmlns(r=http://agavi.org/agavi/config/parts/routing/1.0) " .
            "xpointer(//ae:configuration[@context='console']/*)"
        );
        $routes_node = $document->createElement('routes');
        $routes_node->appendChild($xi_include);
        $package_route->appendChild($routes_node);
        $parent_node->appendChild($package_route);

        return $package_route;
    }

    protected function getModuleMetaData($module_name)
    {
        $meta_data_file = sprintf(
            '%s/%s/module.ini',
            AgaviConfig::get('core.module_dir'),
            $module_name
        );
        if (!is_readable($meta_data_file)) {
            return null;
        }

        $meta_data = parse_ini_file($meta_data_file);
        $required_keys = [ 'vendor', 'package' ];
        foreach ($required_keys as $required_key) {
            if (!isset($meta_data[$required_key])) {
                throw new RuntimeError(
                    sprintf(
                        'Missing required meta-data key "%s" for module %s within meta-data file %s',
                        $required_key,
                        $module_name,
                        $meta_data_file
                    )
                );
            }
        }

        return $meta_data;
    }

    protected function extractModuleNameFromPath($path)
    {
        return str_replace(
            '/config/routing.xml',
            '',
            str_replace(AgaviConfig::get('core.app_dir').'/modules/', '', $path)
        );
    }
}
