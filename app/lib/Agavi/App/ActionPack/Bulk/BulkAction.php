<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Bulk;

use Honeybee\FrameworkBinding\Agavi\App\Base\Action;
use Honeybee\Model\Command\Bulk\BulkOperation;
use Honeybee\Model\Command\Bulk\BulkMetaData;
use Honeybee\Common\Util\StringToolkit;
use AgaviRequestDataHolder;
use Exception;

class BulkAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        return 'Input';
    }

    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        $bulk_iterator = $request_data->getParameter('bulk_operations');
        $errors = array();

        foreach ($bulk_iterator as $ops_count => $bulk_operation) {
            try {
                $this->process($bulk_operation);
            } catch (Exception $runtime_error) {
                $errors[] = array(
                    'item_number' => $ops_count,
                    'message' => $runtime_error->getMessage()
                );

                $this->logError(
                    sprintf("Unexpected error occured while processing bulk-item number %d.", $ops_count),
                    $runtime_error
                );
            }
        }

        $error_count = count($errors);
        $total_count = $ops_count + 1;
        $report_data = array(
            'total_count' => $total_count,
            'succeeded' => $total_count - $error_count,
            'falied' => $error_count,
            'errors' => $errors
        );

        $this->setAttribute('report_data', $report_data);

        return 'Success';
    }

    protected function process(BulkOperation $bulk_operation)
    {
        $locator = $this->getServiceLocator();
        $meta_data = $bulk_operation->getMetaData();
        $ar_type = $locator->getAggregateRootTypeMap()->getItem($meta_data->getType());

        $locator->getCommandBus()->post(
            $this->buildCommand($meta_data, $bulk_operation->getPayload())
        );
    }

    protected function buildCommand(BulkMetaData $meta_data, array $payload)
    {
        $command_implementor = $meta_data->getCommand();

        return new $command_implementor(
            array_merge(
                $payload,
                array(
                    'aggregate_root_type' => StringToolkit::asStudlyCaps($meta_data->getType()),
                    'aggregate_root_identifier' => $meta_data->getIdentifier()
                )
            )
        );
    }
}
