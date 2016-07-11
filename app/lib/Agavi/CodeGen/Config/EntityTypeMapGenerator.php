<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Config;

use AgaviConfig;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\Infrastructure\Template\Twig\TwigRenderer;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Trellis\CodeGen\Parser\Config\ConfigIniParser;
use Trellis\CodeGen\Parser\Schema\EntityTypeSchemaXmlParser;
use Trellis\CodeGen\Schema\EntityTypeDefinition;

abstract class EntityTypeMapGenerator implements ConfigGeneratorInterface
{
    const WORKFLOW_NAME_PATTERN = '%s_%s_%s_workflow_default';

    abstract protected function getConfigFileName();

    abstract protected function getTemplateName();

    public function generate($name, array $schema_files)
    {
        $entity_type_map = [];

        foreach ($schema_files as $schema_file) {
            $trellis_schema_file = new SplFileInfo($schema_file);
            $trellis_config = (new ConfigIniParser())->parse(
                sprintf(
                    '%s/%s.ini',
                    $trellis_schema_file->getPath(),
                    $trellis_schema_file->getBasename('.xml')
                )
            );

            $type_schema = (new EntityTypeSchemaXmlParser())->parse($trellis_schema_file->getRealPath());

            $entity_type_definition = $type_schema->getEntityTypeDefinition();
            $vendor_opt = $entity_type_definition->getOptions()->filterByName('vendor');
            $package_opt = $entity_type_definition->getOptions()->filterByName('package');
            if (!$vendor_opt || !$package_opt) {
                throw new RuntimeError(
                    'Missing vendor- and/or package-option for entity-type: ' . $entity_type_definition->getName()
                );
            }

            $entity_type_key = sprintf(
                '%s.%s.%s',
                strtolower($vendor_opt->getValue()),
                StringToolkit::asSnakeCase($package_opt->getValue()),
                StringToolkit::asSnakeCase($entity_type_definition->getName())
            );

            $entity_type_map[$entity_type_key] = [
                'implementor' => sprintf(
                    '%s\\%s%s',
                    $type_schema->getNamespace(),
                    $entity_type_definition->getName(),
                    $trellis_config->getTypeSuffix('Type')
                ),
                'workflow' => $this->getWorkflowConfig($entity_type_definition)
            ];
        }

        $twig_renderer = TwigRenderer::create(
            [
                'twig_options' => [ 'autoescape' => false ],
                'template_paths' => [ __DIR__ . DIRECTORY_SEPARATOR . 'templates' ]
            ]
        );

        $target_path = AgaviConfig::get('core.config_dir') . DIRECTORY_SEPARATOR;
        $target_path .= 'includes' . DIRECTORY_SEPARATOR . $this->getConfigFileName();

        $twig_renderer->renderToFile($this->getTemplateName(), $target_path, [ 'entity_type_map' => $entity_type_map ]);

        return [];
    }

    protected function getWorkflowConfig(EntityTypeDefinition $entity_type_definition)
    {
        $vendor_opt = $entity_type_definition->getOptions()->filterByName('vendor');
        $package_opt = $entity_type_definition->getOptions()->filterByName('package');
        if (!$vendor_opt || !$package_opt) {
            throw new RuntimeError(
                'Missing vendor- and/or package-option for entity-type: ' . $entity_type_definition->getName()
            );
        }

        return [
            'name' => sprintf(
                self::WORKFLOW_NAME_PATTERN,
                strtolower($vendor_opt->getValue()),
                StringToolkit::asSnakeCase($package_opt->getValue()),
                StringToolkit::asSnakeCase($entity_type_definition->getName())
            ),
            'file' => sprintf(
                '/%s_%s/config/%s/workflows.xml',
                $vendor_opt->getValue(),
                $package_opt->getValue(),
                $entity_type_definition->getName()
            )
        ];
    }
}
