<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\FrameworkBinding\Agavi\CodeGen\Trellis\TrellisTargetFinder;
use Honeybee\FrameworkBinding\Agavi\Util\HoneybeeAgaviToolkit;
use Honeybee\FrameworkBinding\Agavi\Validator\TrellisTargetValidator;
use Honeybee\FrameworkBinding\Agavi\Validator\MigrationNameValidator;
use Honeybee\Infrastructure\Migration\MigrationInterface;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\Common\Error\RuntimeError;
use Trellis\CodeGen\Console\GenerateCodeCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class Honeybee_Core_Trellis_GenerateCodeAction extends Action
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $aggregate_root_type = $request_data->getParameter('type');
        $trellis_target = $request_data->getParameter('target');
        $migration_name = $request_data->getParameter('migration_name');

        // Should probably use a service for some of this
        $finder = new TrellisTargetFinder();
        $location = HoneybeeAgaviToolkit::getTypeSchemaPath($aggregate_root_type);

        if ($trellis_target == TrellisTargetValidator::ALL_TARGETS) {
            $trellis_schema_paths = $finder->findAll((array)$location);
        } else {
            $trellis_schema_paths = $finder->findByName($trellis_target, (array)$location);
        }

        foreach ($trellis_schema_paths as $trellis_schema_path) {
            $current_target = pathinfo($trellis_schema_path, PATHINFO_FILENAME);
            $trellis_config_path = sprintf(
                '%s%s%s.ini',
                dirname($trellis_schema_path),
                DIRECTORY_SEPARATOR,
                $current_target
            );

            $command = new GenerateCodeCommand();
            $input = new ArrayInput(
                [
                    'action' => 'gen+dep',
                    '--schema' => $trellis_schema_path,
                    '--config' => $trellis_config_path
                ]
            );
            $output = new BufferedOutput();

            $report = array();
            $report[] = 'Generating trellis target "' . $current_target . '" for type "' . $aggregate_root_type->getName() . '".';
            $report[] = 'Trellis parameters used are: ';
            $report[] = StringToolkit::getAsString($input, true);
            $report[] = '';

            $exit_code = $command->run($input, $output);
            if ($exit_code != 0) {
                $this->setAttribute('errors', explode(PHP_EOL, $output->fetch()));
                return 'Error';
            }

            // Handle copying of generated mappings to selected migration folder
            if ($current_target != 'aggregate_root' && $migration_name != MigrationNameValidator::NONE_MIGRATION) {
                try {
                    $target_name = $request_data->getParameter('type')->getPackagePrefix() . '::migration::view_store';
                    $migration_service = $this->getServiceLocator()->getMigrationService();
                    $migration_list = $migration_service->getMigrationList($target_name);
                    $migrations = $migration_list->filter(function(MigrationInterface $migration) use($migration_name) {
                        return $migration->getVersion() . ':' . $migration->getName() == $migration_name;
                    });

                    if (count($migrations) !== 1) {
                        throw new RuntimeError(sprintf('Unexpected number of migrations found for %s', $migration_name));
                    }

                    // @todo more sophisticated ini parsing
                    $trellis_config = parse_ini_file($trellis_config_path);
                    $mapping_source_path = sprintf(
                        '%s%s%s',
                        dirname($trellis_config_path),
                        DIRECTORY_SEPARATOR,
                        $trellis_config['deploy_path']
                    );

                    if (!is_readable($mapping_source_path)) {
                        throw new RuntimeError(sprintf('Could not find mapping source file at %s', $mapping_source_path));
                    }

                    $mapping_target_path = sprintf(
                        '%s%s%s',
                        dirname((new ReflectionClass($migrations[0]))->getFileName()),
                        DIRECTORY_SEPARATOR,
                        basename($trellis_config['deploy_path'])
                    );

                    if (!is_writable(dirname($mapping_target_path))) {
                        throw new RuntimeError(sprintf('Mapping target path is not writable for %s', $mapping_target_path));
                    }

                    $timestamp = date('YmdHis');
                    $mapping_target_path = str_replace('{{ timestamp }}', $timestamp, $mapping_target_path);
                    rename($mapping_source_path, $mapping_target_path);
                    $this->setAttribute('mapping_target_path', $mapping_target_path);
                } catch (Exception $e) {
                    $this->setAttribute('errors', [ $e->getMessage() ]);
                    return 'Error';
                }
            }

            $output = $output->fetch();
            if (!empty($output)) {
                $report = array_merge($report, explode(PHP_EOL, $output));
            }
        }

        $this->setAttribute('report', $report);

        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Success';
    }

    public function isSecure()
    {
        return false;
    }
}
