<?php

namespace Honeybee\Tests\Mock;

use Honeybee\Projection\ProjectionTypeInterface;
use Honeygavi\Ui\OutputFormat\OutputFormatInterface;
use Honeygavi\Ui\ViewTemplate\ViewTemplateServiceInterface;
use Honeygavi\Ui\ViewTemplate\ViewTemplate;
use Honeygavi\Ui\ViewTemplate\Part\TabList;
use Honeygavi\Ui\ViewTemplate\Part\Tab;
use Honeygavi\Ui\ViewTemplate\Part\PanelList;
use Honeygavi\Ui\ViewTemplate\Part\Panel;
use Honeygavi\Ui\ViewTemplate\Part\RowList;
use Honeygavi\Ui\ViewTemplate\Part\Row;
use Honeygavi\Ui\ViewTemplate\Part\CellList;
use Honeygavi\Ui\ViewTemplate\Part\Cell;
use Honeygavi\Ui\ViewTemplate\Part\GroupList;
use Honeygavi\Ui\ViewTemplate\Part\Group;
use Honeygavi\Ui\ViewTemplate\Part\FieldList;
use Honeygavi\Ui\ViewTemplate\Part\Field;
use Honeybee\Infrastructure\Config\ArrayConfig;
use Honeygavi\Ui\Renderer\GenericSubjectRenderer;

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
