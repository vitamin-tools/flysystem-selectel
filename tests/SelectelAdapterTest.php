<?php

declare(strict_types = 1);

use ArgentCrusade\Flysystem\Selectel\SelectelAdapter;
use ArgentCrusade\Selectel\CloudStorage\Api\ApiClient;
use ArgentCrusade\Selectel\CloudStorage\CloudStorage;
use ArgentCrusade\Selectel\CloudStorage\Collections\Collection;
use ArgentCrusade\Selectel\CloudStorage\Container;
use ArgentCrusade\Selectel\CloudStorage\File;
use ArgentCrusade\Selectel\CloudStorage\FluentFilesLoader;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use PHPUnit\Framework\TestCase;

class SelectelAdapterTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function selectelProvider(): array
    {
        $collection = new Collection([
            [
                'name' => 'path/to/file',
                'content_type' => 'text/plain',
                'bytes' => 1024,
                'last_modified' => '2000-01-01 00:00:00',
            ],
        ]);

        $files = Mockery::mock(FluentFilesLoader::class);
        $files->shouldReceive('withPrefix')->andReturn($files);
        $files->shouldReceive('get')->andReturn($collection);

        $mock = Mockery::mock(Container::class);
        $mock->shouldReceive('type')->andReturn('public');
        $mock->shouldReceive('files')->andReturn($files);

        return [
            [new SelectelAdapter($mock), $mock, $files, $collection],
        ];
    }

    public function metaDataProvider(): array
    {
        $collection = new Collection([
            [
                'name' => 'path/to/file',
                'content_type' => 'text/plain',
                'bytes' => 1024,
                'last_modified' => '2000-01-01 00:00:00',
            ],
        ]);

        $file = Mockery::mock(File::class);
        $file->shouldReceive('path')->andReturn('/path/to/file');
        $file->shouldReceive('directory')->andReturn('/path/to');
        $file->shouldReceive('name')->andReturn('file');
        $file->shouldReceive('lastModifiedAt')->andReturn('2000-01-01 00:00:00');
        $file->shouldReceive('size')->andReturn(1024);
        $file->shouldReceive('contentType')->andReturn('text/plain');
        $file->shouldReceive('isDirectory')->andReturn(false);

        $files = Mockery::mock(FluentFilesLoader::class);
        $files->shouldReceive('withPrefix')->andReturn($files);
        $files->shouldReceive('get')->andReturn($collection);
        $files->shouldReceive('find')->andReturn($file);

        $mock = Mockery::mock( Container::class);
        $mock->shouldReceive('type')->andReturn('public');
        $mock->shouldReceive('files')->andReturn($files);

        $adapter = new SelectelAdapter($mock);

        return [
            [
                'method' => 'mimeType',
                'adapter' => $adapter,
            ],
            [
                'method' => 'lastModified',
                'adapter' => $adapter,
            ],
        ];
    }

    /**
     * @dataProvider metaDataProvider
     */
    public function testMetaData(string $method, FilesystemAdapter $adapter)
    {
        $result = $adapter->{$method}('path');

        self::assertInstanceOf( FileAttributes::class, $result);
    }

    /**
     * @dataProvider selectelProvider
     */
    public function testFileExists(FilesystemAdapter $adapter, $mock, $files)
    {
        $files->shouldReceive('exists')->andReturn(true);

        self::assertTrue($adapter->fileExists('something'));
    }

    /**
     * @dataProvider selectelProvider
     */
    public function testDirectoryExists(FilesystemAdapter $adapter, $mock, $files)
    {
        $directory = Mockery::mock('ArgentCrusade\Selectel\CloudStorage\File');
        $directory->shouldReceive('contentType')->andReturn('application/directory');

        $files->shouldReceive('find')->andReturn($directory);

        self::assertTrue($adapter->directoryExists('some/directory'));
    }

    /**
     * @dataProvider selectelProvider
     */
    public function testRead(FilesystemAdapter $adapter, $mock, $files)
    {
        $file = Mockery::mock('ArgentCrusade\Selectel\CloudStorage\File');
        $file->shouldReceive('read')->andReturn('something');
        $files->shouldReceive('find')->andReturn($file);

        $result = $adapter->read('something');
        self::assertSame('something', $result);
    }

    /**
     * @dataProvider selectelProvider
     */
    public function testReadStream($adapter, $mock, $files)
    {
        $stream = tmpfile();
        fwrite($stream, 'something');

        $file = Mockery::mock('ArgentCrusade\Selectel\CloudStorage\File');
        $file->shouldReceive('readStream')->andReturn($stream);
        $files->shouldReceive('find')->andReturn($file);

        $result = $adapter->readStream('something');
        self::assertIsResource($result);
        self::assertSame('something', fread($result, 1024));

        fclose($stream);
    }
}
