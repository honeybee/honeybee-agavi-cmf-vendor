# Document default values and null values

Each document field has a default and a null value.  
A field's null value is normally also it's default value.  
In short we can sum this up to:

* A null value states the absence of a sepecific (user) value.
* A default value is initially set for a field upon document creation.  

If other behaviour is desired you can configure a custom default value for each field instance.  
Other than a default value that is only set once during document creation,  
a null value can be assigned to a document field as often as desired.  
The default and null value that are supposed to be used by a specific field are exposed by a field's  

* getDefaultValue and
* getNullValue

methods.  

```php
class Field
{
    public function getDefaultValue()
    {
        return $this->getOption('default_value', $this->getNullValue());
    }

    public function getNullValue()
    {
        return NULL;
    }
}
```

When asking a document if it is currently containing a value for a specific field,  
the current value is checked against the field's null value.  

```php
class Document
{
    public function hasValue($fieldname)
    {
        $field = $this->module->getField($fieldname);
        $nullValue = $field->getNullValue();

        return $this->values->get($field) !== $nullValue;
    }
}
```

