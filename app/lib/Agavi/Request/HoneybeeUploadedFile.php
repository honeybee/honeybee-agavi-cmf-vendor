<?php

namespace Honeybee\FrameworkBinding\Agavi\Request;

use AgaviWebRequestDataHolder;
use AgaviUploadedFile;
use ArrayObject;

class HoneybeeUploadedFile extends AgaviUploadedFile
{
    const PROPERTY_LOCATION = 'honeybee_location';
    const PROPERTY_MIMETYPE = 'honeybee_mimetype';
    const PROPERTY_EXTENSION = 'honeybee_extension';
    const PROPERTY_FILESIZE = 'honeybee_filesize';

    public function __construct(
        array $data = [],
        $flags = ArrayObject::ARRAY_AS_PROPS,
        $iterator_class = 'ArrayIterator'
    ) {
        $defaults = [
            self::PROPERTY_LOCATION => null,
            self::PROPERTY_MIMETYPE => null,
            self::PROPERTY_EXTENSION => null,
            self::PROPERTY_FILESIZE => 0,
        ];

        parent::__construct(array_merge($defaults, $data), $flags, $iterator_class);
    }

    public function getLocation()
    {
        return $this[self::PROPERTY_LOCATION];
    }

    public function getMimetype()
    {
        return $this[self::PROPERTY_MIMETYPE];
    }

    public function getExtension()
    {
        return $this[self::PROPERTY_EXTENSION];
    }

    public function getFilesize()
    {
        return $this[self::PROPERTY_FILESIZE];
    }

    public function setLocation($location)
    {
        $this[self::PROPERTY_LOCATION] = $location;
    }

    public function setMimetype($mimetype)
    {
        $this[self::PROPERTY_MIMETYPE] = $mimetype;
    }

    public function setExtension($ext)
    {
        $this[self::PROPERTY_EXTENSION] = $ext;
    }

    public function setFilesize($size)
    {
        $this[self::PROPERTY_FILESIZE] = $size;
    }

    public function hasHoneybeeProperties()
    {
        return (
            $this[self::PROPERTY_LOCATION] !== null &&
            $this[self::PROPERTY_MIMETYPE] !== null &&
            $this[self::PROPERTY_EXTENSION] !== null &&
            $this[self::PROPERTY_FILESIZE] !== 0
        );
    }

    public function getHoneybeeProperties()
    {
        return [
            'location' => $this[self::PROPERTY_LOCATION],
            'mimetype' => $this[self::PROPERTY_MIMETYPE],
            'extension' => $this[self::PROPERTY_EXTENSION],
            'filesize' => $this[self::PROPERTY_FILESIZE]
        ];
    }
}
