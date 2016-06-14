<?php

namespace Honeybee\Tests;

use Honeybee\Projection\ProjectionTypeInterface;
use Honeybee\Ui\OutputFormat\OutputFormatInterface;
use Honeybee\Ui\ViewTemplate\ViewTemplateServiceInterface;
use Honeybee\Ui\ViewTemplate\ViewTemplate;
use Honeybee\Ui\ViewTemplate\Part\TabList;
use Honeybee\Ui\ViewTemplate\Part\Tab;
use Honeybee\Ui\ViewTemplate\Part\PanelList;
use Honeybee\Ui\ViewTemplate\Part\Panel;
use Honeybee\Ui\ViewTemplate\Part\RowList;
use Honeybee\Ui\ViewTemplate\Part\Row;
use Honeybee\Ui\ViewTemplate\Part\CellList;
use Honeybee\Ui\ViewTemplate\Part\Cell;
use Honeybee\Ui\ViewTemplate\Part\GroupList;
use Honeybee\Ui\ViewTemplate\Part\Group;
use Honeybee\Ui\ViewTemplate\Part\FieldList;
use Honeybee\Ui\ViewTemplate\Part\Field;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeybee\Ui\Renderer\GenericSubjectRenderer;

class TestViewTemplateService implements ViewTemplateServiceInterface
{
    public function getViewTemplate($scope, $view_template_name, OutputFormatInterface $output_format = null)
    {
        return new ViewTemplate(
            'test-view',
            new TabList([
                new Tab('test-tab', new PanelList([
                    new Panel('test-panel', new RowList([
                        new Row(
                            new CellList([
                                new Cell(
                                    new GroupList([
                                        new Group('test-group', new FieldList([
                                            new Field('test-field', new ArrayConfig([
                                                'renderer' => GenericSubjectRenderer::CLASS
                                            ]))
                                        ]))
                                    ])
                                )
                            ])
                        )
                    ]))
                ]))
            ])
        );
    }

    public function hasViewTemplate($scope, $view_template_name, OutputFormatInterface $output_format = null)
    {
        return true;
    }

    public function getViewTemplateScopes()
    {
        return [ 'honeybee.system_account.user' ];
    }

    public function getViewTemplateNames($scope)
    {
        return [ 'test-view' ];
    }

    public function createViewTemplate(
        $view_template_name,
        ProjectionTypeInterface $resource_type,
        array $attribute_names = []
    ) {
    }
}