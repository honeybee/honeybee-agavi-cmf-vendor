<?php

namespace Honeybee\FrameworkBinding\Agavi\Request;

use AgaviRequestDataHolder;
use AgaviWebRequestDataHolder;
use Honeybee\FrameworkBinding\Agavi\Request\HoneybeeUploadedFile;
use Honeybee\FrameworkBinding\Agavi\Request\OutputtypesRequestDataHolderInterface;

class WebRequestDataHolder extends AgaviWebRequestDataHolder implements OutputtypesRequestDataHolderInterface
{
    /**
     * @var string implementor to use for AgaviUploadedFile
     */
    protected $uploadedFileClass = HoneybeeUploadedFile::CLASS;

    /**
     * @constant source name of output types
     */
    const SOURCE_OUTPUTTYPES = 'outputtypes';

    /**
     * @var array array of output types used for the request
     */
    protected $outputtypes = [];

    /**
     * Constructor
     *
     * @param array associative array of request data source names and data arrays
     */
    public function __construct(array $data = [])
    {
        $this->registerSource(self::SOURCE_OUTPUTTYPES, $this->outputtypes);

        parent::__construct($data);
    }

    /**
     * Clear all output types.
     */
    public function clearOutputtypes()
    {
        $this->outputtypes = [];
    }

    /**
     * Retrieve all output types.
     *
     * @return array list of output types.
     */
    public function &getOutputtypes()
    {
        return $this->outputtypes;
    }

    /**
     * Get an output type.
     *
     * @param string case-insensitive name of an output type
     * @param string default value
     *
     * @return string output type value (that is, the name), or null if output type wasn't set.
     */
    public function &getOutputtype($name, $default = null)
    {
        $name = str_replace('-', '_', strtoupper($name));
        if(isset($this->outputtypes[$name]) || array_key_exists($name, $this->outputtypes)) {
            return $this->outputtypes[$name];
        }

        return $default;
    }

    /**
     * Check if an output type exists.
     *
     * @param string case-insensitive name of an output type
     *
     * @return bool true if the output type was set for the current request; false otherwise
     */
    public function hasOutputtype($name)
    {
        $name = str_replace('-', '_', strtoupper($name));
        return (isset($this->outputtypes[$name]) || array_key_exists($name, $this->outputtypes));
    }

    /**
     * @param string output type name
     *
     * @return bool true if value of output type is set; false otherwise
     */
    public function isOutputtypeValueEmpty($name)
    {
        return ($this->getOutputtype($name) === null);
    }
    /**
     * Set an output type.
     *
     * The output type name is normalized before storing it.
     *
     * @param string output type name
     * @param string output type value (that is, the name)
     */
    public function setOutputtype($name, $value)
    {
        $this->outputtypes[str_replace('-', '_', strtoupper($name))] = $value;
    }

    /**
     * Set an array of output types.
     *
     * @param array associative array of output types and their values.
     */
    public function setOutputtypes(array $output_types)
    {
        $this->outputtypes = array_merge($this->outputtypes, $output_types);
    }

    /**
     * Remove an output type.
     *
     * @param string Case-insensitive name of an output type
     *
     * @return string value of the removed output type or null
     */
    public function &removeOutputtype($name)
    {
        $retval = null;
        $name = str_replace('-', '_', strtoupper($name));
        if(isset($this->outputtypes[$name]) || array_key_exists($name, $this->outputtypes)) {
            $retval =& $this->outputtypes[$name];
            unset($this->outputtypes[$name]);
        }
        return $retval;
    }

    /**
     * @return array indexed array of output type names
     */
    public function getOutputtypeNames()
    {
        return array_keys($this->outputtypes);
    }

    /**
     * Merge in output types from another request data holder.
     *
     * @param AgaviRequestDataHolder The other request data holder.
     */
    public function mergeOutputtypes(AgaviRequestDataHolder $other)
    {
        if($other instanceof OutputtypesRequestDataHolderInterface) {
            $this->setOutputtypes($other->getOutputtypes());
        }
    }
}
