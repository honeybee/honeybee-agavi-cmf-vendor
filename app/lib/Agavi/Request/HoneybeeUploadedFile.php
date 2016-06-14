<?php

namespace Honeybee\FrameworkBinding\Agavi\Request;

use AgaviUploadedFile;
use AgaviWebRequestDataHolder;
use ArrayObject;
use Trellis\Runtime\Attribute\HandlesFileInterface;

class HoneybeeUploadedFile extends AgaviUploadedFile
{
    const PROPERTY_LOCATION = 'honeybee_location';
    const PROPERTY_FILENAME = 'honeybee_filename';
    const PROPERTY_FILESIZE = 'honeybee_filesize';
    const PROPERTY_MIMETYPE = 'honeybee_mimetype';
    const PROPERTY_EXTENSION = 'honeybee_extension';
    const PROPERTY_WIDTH = 'honeybee_width';
    const PROPERTY_HEIGHT = 'honeybee_height';

    public function __construct(
        array $data = [],
        $flags = ArrayObject::ARRAY_AS_PROPS,
        $iterator_class = 'ArrayIterator'
    ) {
        $defaults = [
            self::PROPERTY_LOCATION => null,
            self::PROPERTY_FILENAME => null,
            self::PROPERTY_MIMETYPE => null,
            self::PROPERTY_EXTENSION => null,
            self::PROPERTY_FILESIZE => 0,
            self::PROPERTY_WIDTH => 0,
            self::PROPERTY_HEIGHT => 0,
        ];

        parent::__construct(array_merge($defaults, $data), $flags, $iterator_class);
    }

    public function getLocation()
    {
        return $this[self::PROPERTY_LOCATION];
    }

    public function getMimetype($charset = false)
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

    public function getFilename()
    {
        return $this[self::PROPERTY_FILENAME];
    }

    public function getWidth()
    {
        return $this[self::PROPERTY_WIDTH];
    }

    public function getHeight()
    {
        return $this[self::PROPERTY_HEIGHT];
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

    public function setFilename($name)
    {
        $this[self::PROPERTY_FILENAME] = $name;
    }

    public function setWidth($width)
    {
        $this[self::PROPERTY_WIDTH] = $width;
    }

    public function setHeight($height)
    {
        $this[self::PROPERTY_HEIGHT] = $height;
    }

    public function hasHoneybeeProperties()
    {
        return (
            $this[self::PROPERTY_LOCATION] !== null &&
            $this[self::PROPERTY_FILENAME] !== null &&
            $this[self::PROPERTY_MIMETYPE] !== null &&
            $this[self::PROPERTY_FILESIZE] !== 0
        );
    }

    public function getHoneybeeProperties()
    {
        return [
            HandlesFileInterface::DEFAULT_PROPERTY_LOCATION => $this[self::PROPERTY_LOCATION],
            HandlesFileInterface::DEFAULT_PROPERTY_FILENAME => $this[self::PROPERTY_FILENAME],
            HandlesFileInterface::DEFAULT_PROPERTY_FILESIZE => $this[self::PROPERTY_FILESIZE],
            HandlesFileInterface::DEFAULT_PROPERTY_MIMETYPE => $this[self::PROPERTY_MIMETYPE]
        ];
    }
}
