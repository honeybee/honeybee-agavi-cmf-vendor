<?php

use Honeybee\Common\Util\StringToolkit;
use Honeygavi\App\Base\Action;
use Honeygavi\CodeGen\Skeleton\SkeletonGenerator;

class Honeybee_Core_Util_GenerateCodeAction extends Action
{
    const DEFAULT_SKELETON_GENERATOR = SkeletonGenerator::CLASS;

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $parameters = $request_data->getParameters();
        $skeleton_name = $request_data->getParameter('skeleton');
        $target_path = $request_data->getParameter('target_path');
        $type_name = $request_data->getParameter('type');

        // in case of missing custom skeleton validation and missing CLI option, we need to use a default generator
        $skeleton_generator = $request_data->getParameter('skeleton_generator', self::DEFAULT_SKELETON_GENERATOR);
        $parameters['skeleton_generator'] = $skeleton_generator;
        $parameters['reporting_enabled'] = !$request_data->getParameter('quiet');

        try {
            $report = [];
            $report[] = 'Generating skeleton "' . $skeleton_name . '" using generator "' . $skeleton_generator . '".';
            $report[] = 'Generator parameters used are: ';
            $report[] = StringToolkit::getAsString($parameters, true);
            $report[] = '';
            $generator = new $skeleton_generator($skeleton_name, $target_path, $parameters);
            $generator->generate();

            $this->setAttribute('report', array_merge($report, $generator->getReport()));
        } catch (Exception $e) {
            $this->setAttribute('errors', $e->getMessage() . PHP_EOL);
            return 'Error';
        }

        return 'Success';
    }

    public function validate(AgaviRequestDataHolder $request_data)
    {
        $target_path = $request_data->getParameter('target_path');
        $skeleton_generator = $request_data->getParameter('skeleton_generator');

        if (empty($target_path) && empty($skeleton_generator)) {
            $this->setAttribute(
                'errors',
                'At least one of the following parameters needs to be provided: "target_path" or "skeleton_generator".'
                . PHP_EOL . 'This may be done via CLI arguments or a skeleton_parameters.validate.xml file.'
            );

            return false;
        }

        if (empty($target_path) && $skeleton_generator === self::DEFAULT_SKELETON_GENERATOR) {
            $this->setAttribute(
                'errors',
                'The default skeleton generator needs a "target_path" provided ' .
                'via CLI or ' . SkeletonFinder::VALIDATION_FILE . ' file.'
            );

            return false;
        }

        return true;
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
