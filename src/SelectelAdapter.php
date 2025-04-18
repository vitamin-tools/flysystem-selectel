<?php

declare(strict_types = 1);

namespace ArgentCrusade\Flysystem\Selectel;

use ArgentCrusade\Selectel\CloudStorage\Contracts\FileContract;
use League\Flysystem\Config;
use ArgentCrusade\Selectel\CloudStorage\Contracts\ContainerContract;
use ArgentCrusade\Selectel\CloudStorage\Exceptions\FileNotFoundException;
use ArgentCrusade\Selectel\CloudStorage\Exceptions\UploadFailedException;
use ArgentCrusade\Selectel\CloudStorage\Exceptions\ApiRequestFailedException;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use LogicException;

class SelectelAdapter implements FilesystemAdapter
{
    public function __construct(
        protected readonly ContainerContract $container,
    ) {}

    public function fileExists(string $path): bool
    {
        return $this->container->files()->exists($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            $this->container->files()->find($path)->size(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $path): string
    {
        try {
            $file = $this->getFile($path);
        } catch (FileNotFoundException $e) {
            throw UnableToReadFile::fromLocation($path, 'File not found', $e);
        }

        return $file->read();
    }

    /**
     * {@inheritdoc}
     */
    public function readStream(string $path)
    {
        try {
            $file = $this->getFile($path);
        } catch (FileNotFoundException $e) {
            throw UnableToReadFile::fromLocation($path, 'File not found', $e);
        }

        $stream = $file->readStream();

        rewind($stream);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents(string $path = '', bool $deep = false): iterable
    {
        $prefixer = new PathPrefixer($path, DIRECTORY_SEPARATOR);

        $files = $this->container->files()->fromDirectory($path)->all();

        /** @var array{
         *     bytes:int,
         *     hash:string,
         *     name:string,
         *     content_type:string,
         *     last_modified:string,
         *     filename:string
         * } $fileInfo
         */
        foreach ($files as $fileInfo) {
            yield $this->fileInfoToAttributes($prefixer, $fileInfo);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->writeToContainer('String', $path, $contents);
        } catch (UploadFailedException $e) {
            throw UnableToWriteFile::atLocation($path, 'Upload failed', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->writeToContainer('Stream', $path, $contents);
        } catch (UploadFailedException $e) {
            throw UnableToWriteFile::atLocation($path, 'Upload failed', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->getFile($source)->rename($destination);
        } catch (ApiRequestFailedException $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->getFile($source)->copy($destination);
        } catch (ApiRequestFailedException $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $path): void
    {
        try {
            $this->getFile($path)->delete();
        } catch (ApiRequestFailedException $e) {
            throw UnableToDeleteFile::atLocation($path, 'API request failed', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDirectory(string $path): void
    {
        try {
            $this->container->deleteDir($path);
        } catch (ApiRequestFailedException $e) {
            throw UnableToDeleteDirectory::atLocation($path, 'API request failed', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->container->createDir($path);
        } catch (ApiRequestFailedException $e) {
            throw UnableToCreateDirectory::atLocation($path, 'API request failed', $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function directoryExists(string $path): bool
    {
        try {
            $directory = $this->container->files()->find($path);
        } catch (FileNotFoundException $e) {
            throw UnableToCheckExistence::forLocation($path, $e);
        }

        return $directory->contentType() === 'application/directory';
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, Visibility::PUBLIC);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw new LogicException(
            sprintf(
                '%s does not support visibility setting. Path: %s, visibility: %s',
                get_class($this),
                $path,
                $visibility
            ),
        );
    }

    public function mimeType(string $path): FileAttributes
    {
        return new FileAttributes(
            $path,
            null,
            null,
            null,
            $this->container->files()->find($path)->contentType(),
        );
    }

    public function lastModified(string $path): FileAttributes
    {
        $lastModified = $this->container->files()->find($path)->lastModifiedAt();

        return new FileAttributes(
            $path,
            null,
            null,
            strtotime($lastModified),
        );
    }

    /**
     * Writes string or stream to container.
     *
     * @param string          $type    Upload type
     * @param string          $path    File path
     * @param string|resource $payload String content or Stream resource
     * @throws UploadFailedException
     */
    protected function writeToContainer(string $type, string $path, mixed $payload): void
    {
        $this->container->{'uploadFrom'.$type}($path, $payload);
    }

    /**
     * Loads file from container.
     *
     * @param string $path Path to file.
     *
     * @return FileContract
     */
    protected function getFile(string $path): FileContract
    {
        return $this->container->files()->find($path);
    }

    protected function fileInfoToAttributes(
        PathPrefixer $prefixer,
        array $fileInfo,
    ): StorageAttributes {
        $path = $prefixer->stripPrefix($fileInfo['name']);

        $isDirectory = $fileInfo['content_type'] === 'application/directory';

        if ($isDirectory) {
            return new DirectoryAttributes(
                $path,
                Visibility::PUBLIC,
                strtotime($fileInfo['last_modified'])
            );
        } else {
            return new FileAttributes(
                $path,
                $fileInfo['bytes'],
                Visibility::PUBLIC,
                strtotime($fileInfo['last_modified']),
                $fileInfo['content_type'],
            );
        }
    }
}
