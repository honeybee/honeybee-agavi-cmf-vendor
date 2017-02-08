<?php

namespace Honeybee\Ui\Renderer;

use DateTimeInterface;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\Util\ArrayToolkit;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\EntityInterface;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\Config\SettingsInterface;
use Honeybee\Infrastructure\Expression\ExpressionServiceInterface;
use Honeybee\Infrastructure\Template\TemplateRendererInterface;
use Honeybee\Projection\ProjectionInterface;
use Honeybee\Ui\OutputFormat\OutputFormatInterface;
use Honeybee\Ui\UrlGeneratorInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\AttributeValuePath;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;
use Trellis\Runtime\ValueHolder\ComplexValueInterface;

abstract class AttributeRenderer extends Renderer
{
    const STATIC_TRANSLATION_PATH = 'fields';

    protected $attribute;

    protected function validate()
    {
        if (!$this->getPayload('resource') instanceof EntityInterface) {
            throw new RuntimeError('Payload "resource" must implement: ' . EntityInterface::CLASS);
        }

        if (!$this->getPayload('attribute') instanceof AttributeInterface) {
            throw new RuntimeError(
                sprintf('Instance of "%s" necessary.', AttributeInterface::CLASS)
            );
        }

        $this->attribute = $this->getPayload('attribute');
    }

    protected function doRender()
    {
        return $this->getTemplateRenderer()->render($this->getTemplateIdentifier(), $this->getTemplateParameters());
    }

    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/attribute/as_itemlist_item_cell.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $attribute_name = $this->attribute->getName();
        $attribute_path = $this->attribute->getPath();
        $field_name = $this->getOption('field_name', $attribute_name);

        $params['field_id'] = 'randomId-' . rand(); // @todo still random but nicer ids?
        $params['field_name'] = $field_name;
        $params['grouped_field_name'] = $this->getGroupedInputFieldName();
        $params['grouped_base_path'] = $this->getGroupedInputFieldName();
        $params['attribute_name'] = $attribute_name;
        $params['attribute_path'] = $attribute_path;
        $params['attribute_value'] = $this->determineAttributeValue($attribute_name);
        $params['attribute_value_is_null_value'] = is_null($params['attribute_value']);
        $params['is_embedded'] = $this->getOption('is_within_embed_template', false);

        return $params;
    }

    protected function getDefaultTranslationDomain()
    {
        return sprintf(
            '%s.%s',
            $this->attribute->getRootType()->getPrefix(),
            self::STATIC_TRANSLATION_PATH
        );
    }

    protected function getGroupedInputFieldName()
    {
        $entity_type = $this->attribute->getType();

        $group_parts = $this->getOption('group_parts', []);
        if ($group_parts instanceof SettingsInterface) {
            $group_parts = $group_parts->toArray();
        } elseif (!is_array($group_parts)) {
            throw new RuntimeError(
                'Invalid value type given for "group_parts" option. Only arrays are supported here.'
            );
        }

        $value_path = $this->getOption('attribute_value_path');
        $field_specific_group_parts = explode('.', $this->attribute->getPath());
        if (!empty($value_path)) {
            $value_path_group_parts = explode('.', $value_path);
            $calc_index = 1;
            foreach ($value_path_group_parts as $actual_idx => $value_path_group_part) {
                if ($calc_index % 2 === 0) {
                    if (preg_match('/[\w\*]+\[(\d+)\]/', $value_path_group_part, $matches)) {
                        $group_parts[] = $matches[1];
                    } else {
                        throw new RuntimeError(
                            sprintf(
                                'Invalid attribute_value_path "%s" given to renderer "%s" for field "%s".' .
                                ' Missing expected embed index within path specification.',
                                $value_path,
                                static::CLASS,
                                $this->attribute->getPath()
                            )
                        );
                    }
                } else {
                    $group_parts[] = $value_path_group_part;
                }
                $calc_index++;
            }
        } else {
            if ($this->attribute->getType()->isRoot()) {
                $group_parts = array_merge($group_parts, explode('.', $this->attribute->getPath()));
            } else {
                $group_parts[] = $this->attribute->getName();
            }
        }

        return ArrayToolkit::flattenToArrayPath($group_parts);
    }

    protected function determineAttributeValue($attribute_name)
    {
        $value = '';

        if ($this->hasOption('value')) {
            return $this->getOption('value');
        }

        $value_path = $this->getOption('attribute_value_path');
        if (!empty($value_path)) {
            $value = AttributeValuePath::getAttributeValueByPath($this->getPayload('resource'), $value_path);
        } else {
            $value = $this->getPayload('resource')->getValue($attribute_name);
        }

        // @todo introduce nested rendering or smarter mechanisms or error message for resources and other known types?
        if (is_object($value)) {
            if ($value instanceof DateTimeInterface) {
                $value = $value->format(TimestampAttribute::FORMAT_ISO8601);
            } elseif (!$value instanceof ComplexValueInterface) {
                $value = sprintf(
                    'Attribute "%s" (type "%s") – value of type object (%s): %s',
                    $attribute_name,
                    get_class($this->attribute),
                    get_class($value),
                    StringToolkit::getObjectAsString($value)
                );
            } else {
                // it's a complex value so we should not convert the object to string or similar
                // the specific renderer for that attribute might want to use the actual object
            }
        } elseif (is_array($value)) {
            $value = sprintf(
                'Attribute "%s" – value of type array with keys: %s',
                $attribute_name,
                print_r(array_keys($value), true)
            );
        } else {
            // $value = StringToolkit::getAsString($value);
        }

        return $value;
    }

    protected function evaluateExpression($expression)
    {
        $expression_params = [ 'resource' => $this->getPayload('resource') ];

        return $this->expression_service->evaluate($expression, $expression_params);
    }

    /**
     * Attributes can have different field specific translation keys. There can be translations depending
     * on the current state of the root resource being rendered.
     *
     * Example: Field "foo" (of same name attribute "foo") will lead to translation attempts for the defined
     * default translation keys. This means in translation you can have keys like "foo.input_help" and
     * "foo.input_help.inactive" while the "translations" key in the template parameters contains on the
     * "input_help" and "input_help.inactive" if those translation keys lead to actual translations (they exist).
     *
     * Check the Workflow.xml of the interested Resource for a list of available states. If no 'per-state' translation
     * is defined then the general translation key will be used as fallback:
     *
     *      e.g. 'input_help' if 'input_help.inactive' has not been defined
     *
     * If the fallback doesn't exist either, the translation will not be returned at all.
     *
     * @return array Translated strings to use in the template
     */
    protected function getTranslations($translation_domain = null)
    {
        $translation_keys = $this->getTranslationKeys($translation_domain);
        $translations = [];

        $resource_current_state = $this->getPayload('resource') instanceof ProjectionInterface
            ? $this->getPayload('resource')->getWorkflowState()
            : $this->getPayload('resource')->getRoot()->getWorkflowState();

        $field_name = $this->getOption('field_name', $this->attribute->getName());

        foreach ($translation_keys as $key) {
            $translation_key = sprintf('%s.%s.%s', $field_name, $key, $resource_current_state);
            $translation = $this->_($translation_key, $translation_domain, null, null, '');

            // if a translation doesn't exist for the current state fallback to the stateless translation
            if (empty($translation)) {
                $translation_key = sprintf('%s.%s', $field_name, $key);
                $translation = $this->_($translation_key, $translation_domain, null, null, '');
            }
            // if there's a non-empty field specific translation now, remember it w/o field_name
            if (!empty($translation)) {
                $translations[$key] = $translation;
                //$translations[$index] = $translation;
            }
        }

        return $translations;
    }

    protected function getDefaultTranslationKeys()
    {
        $default_translation_keys = parent::getDefaultTranslationKeys();

        // will be available as "translations.input_help" in the twig template while the actual
        // translation key lookup will be for "field_name.input_help" in the "…fields" translation_domain
        $field_translation_keys = [
            'input_help',
            'input_hint',
            'input_focus_hint',
            'placeholder',
            'title',
        ];

        return array_unique(array_merge($default_translation_keys, $field_translation_keys));
    }

    protected function isReadonly()
    {
        return (bool)($this->attribute->getOption('mirrored', false) || $this->getOption('readonly', false));
    }

    protected function isRequired()
    {
        return (bool)($this->getOption('required', $this->attribute->getOption('mandatory', false)));
    }

    protected function getInputViewTemplateNameSuffixes($output_format_name = '')
    {
        $input_view_template_name_suffixes = (array)$this->getOption(
            'input_view_template_name_suffixes',
            (array)$this->environment->getSettings()->get(
                'ui.input_view_template_name_suffixes',
                [ 'create', 'modify' ]
            )
        );
        // by convention is possible to specify the output format name after the view name
        if (!empty($output_format_name)) {
            foreach ($input_view_template_name_suffixes as $input_suffix) {
                $input_view_template_name_suffixes[] = sprintf('%s.%s', $input_suffix, $output_format_name);
            }
        }

        return $input_view_template_name_suffixes;
    }
}
