<?php

use Honeybee\Common\Util\StringToolkit;
use Honeygavi\App\Base\View;
use Honeygavi\Template\Twig\TwigRenderer;
use Honeybee\Common\Error\RuntimeError;

class Honeybee_Core_Migrate_Create_CreateSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $migration_description = $request_data->getParameter('description');
        $migration_timestamp = date('YmdHis');
        $migration_slug = StringToolkit::asSnakeCase(trim($request_data->getParameter('name', 'default')));
        $migration_name = StringToolkit::asStudlyCaps($migration_slug);
        $migration_dir = $this->getAttribute('migration_dir');

        // Bit of a hack to build namespace
        if (!preg_match('#.+/app/(?:modules|migration)/(\w+_?)(?:$|/.+)#', $migration_dir, $matches)) {
            throw new RuntimeError(sprintf('Could not find namespace info in path %s', $migration_dir));
        }

        $namespace_parts = explode('_', $matches[1]);
        if (count($namespace_parts) == 1) {
            // @todo app migration - introduce a project root namespace setting
            $namespace_parts = [ 'Your', 'Application' ];
        }

        // And a hack to determine the technology namespace
        $target = $request_data->getParameter('target');
        if (strpos($target, 'event_source')) {
            $technology = 'CouchDb';
        } elseif (strpos($target, 'view_store')) {
            $technology = 'Elasticsearch';
        } else {
            $technology = 'RabbitMq';
        }

        $migration_filepath = sprintf(
            '%1$s%2$s%3$s_%4$s%2$s%4$s.php',
            $migration_dir,
            DIRECTORY_SEPARATOR,
            $migration_timestamp,
            $migration_slug
        );

        $twig_renderer = TwigRenderer::create([
            'template_paths' => [ __DIR__ ]
        ]);

        $twig_renderer->renderToFile(
            $technology . 'Migration.tpl.twig',
            $migration_filepath,
            [
                'name' => $migration_name,
                'timestamp' => $migration_timestamp,
                'description' => $migration_description,
                'folder' => $migration_dir,
                'filepath' => $migration_filepath,
                'vendor_prefix' => $namespace_parts[0],
                'package_prefix' => $namespace_parts[1],
                'technology' => $technology,
                'project_prefix' => AgaviConfig::get('core.project_prefix')
            ]
        );

        return $this->cliMessage('-> migration template was created here:' . PHP_EOL . $migration_filepath . PHP_EOL);
    }
}
