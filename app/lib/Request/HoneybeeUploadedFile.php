<?php

namespace Honeygavi\Request;

use AgaviUploadedFile;
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

    protected static $indexMap = [
        self::PROPERTY_LOCATION => 'location',
        self::PROPERTY_FILENAME => 'filename',
        self::PROPERTY_FILESIZE => 'filesize',
        self::PROPERTY_MIMETYPE => 'mimeType',
        self::PROPERTY_EXTENSION => 'extension',
        self::PROPERTY_WIDTH => 'width',
        self::PROPERTY_HEIGHT => 'height'
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $defaults = [
            self::PROPERTY_LOCATION => null,
            self::PROPERTY_FILENAME => null,
            self::PROPERTY_MIMETYPE => null,
            self::PROPERTY_EXTENSION => null,
            self::PROPERTY_FILESIZE => 0,
            self::PROPERTY_WIDTH => 0,
            self::PROPERTY_HEIGHT => 0
        ];

        $array = array_merge($defaults, $data);

        // override parent resetting of 'is_moved'
        if (isset($array['is_moved'])) {
            $this->isMoved = $array['is_moved'];
        }

        foreach (self::$indexMap as $index => $property) {
            if (isset($array[$index])) {
                $this->$property = $array[$index];
            }
        }
    }

    // overriding because parent uses self instead of static map
    public function offsetGet($key)
    {
        $property = self::$indexMap[$key];
        return $this->$property;
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

    public function createCopyWith(array $data)
    {
        return new static(array_merge(
            [
                'name' => $this->name,
                'type' => $this->type,
                'size' => $this->size,
                'tmp_name' => $this->tmpName,
                'error' => $this->error,
                'is_uploaded_file' => $this->isUploadedFile,
                'is_moved' => $this->isMoved,
                'contents' => $this->contents,
                'stream' => $this->stream,
            ],
            $data
        ));
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
