<?php

use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Infrastructure\Template\Twig\TwigRenderer;

class Honeybee_Core_Fixture_Create_CreateSuccessView extends View
{
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $fixture_timestamp = date('YmdHis');
        $fixture_slug = StringToolkit::asSnakeCase(trim($request_data->getParameter('name', 'default')));
        $fixture_name = StringToolkit::asStudlyCaps($fixture_slug);
        $fixture_dir = $this->getAttribute('fixture_dir');

        // Bit of a hack to build namespace
        if (!preg_match('#.+/app/modules/(\w+?)/.+#', $fixture_dir, $matches)) {
            throw new RuntimeError(sprintf('Could not find namespace info in path %s', $fixture_dir));
        }
        $namespace_parts = explode('_', $matches[1]);

        $fixture_filepath = sprintf(
            '%1$s%2$s%3$s_%4$s%2$s%4$s.php',
            $fixture_dir,
            DIRECTORY_SEPARATOR,
            $fixture_timestamp,
            $fixture_slug
        );

        $twig_renderer = TwigRenderer::create(
            [
                'template_paths' => [ __DIR__ ]
            ]
        );

        $twig_renderer->renderToFile(
            'Fixture.tpl.twig',
            $fixture_filepath,
            [
                'name' => $fixture_name,
                'timestamp' => $fixture_timestamp,
                'folder' => $fixture_dir,
                'filepath' => $fixture_filepath,
                'vendor_prefix' => $namespace_parts[0],
                'package_prefix' => $namespace_parts[1]
            ]
        );

        touch(
            sprintf(
                '%1$s%2$s%3$s_%4$s%2$s%3$s-fixture-data.json',
                $fixture_dir,
                DIRECTORY_SEPARATOR,
                $fixture_timestamp,
                $fixture_slug
            )
        );

        $fixture_files_dir = sprintf(
            '%1$s%2$s%3$s_%4$s%2$s%5$s',
            $fixture_dir,
            DIRECTORY_SEPARATOR,
            $fixture_timestamp,
            $fixture_slug,
            'files'
        );
        mkdir($fixture_files_dir);

        return $this->cliMessage('-> fixture template was generated here:' . PHP_EOL . $fixture_filepath . PHP_EOL);
    }
}
