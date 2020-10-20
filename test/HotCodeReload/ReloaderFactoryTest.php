<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\HotCodeReload;

use Laminas\ServiceManager\ServiceManager;
use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Mezzio\Swoole\HotCodeReload\ReloaderFactory;
use Mezzio\Swoole\Log\StdoutLogger;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ReloaderFactoryTest extends TestCase
{
    use AttributeAssertionTrait;

    /** @var ServiceManager */
    private $container;

    /**
     * @var FileWatcherInterface|MockObject
     * @psalm-var MockObject&FileWatcherInterface
     */
    private $fileWatcher;

    protected function setUp(): void
    {
        $this->fileWatcher = $this->createMock(FileWatcherInterface::class);
        $this->container   = new ServiceManager();
        $this->container->setAllowOverride(true);
        $this->container->setService(FileWatcherInterface::class, $this->fileWatcher);

        parent::setUp();
    }

    /**
     * @dataProvider provideServiceManagerServicesWithEmptyConfigurations
     * @psalm-param array<array-key, array<array-key, array>> $services
     */
    public function testCreateUnconfigured(array $services): void
    {
        $this->container->configure(['services' => $services]);
        $reloader = (new ReloaderFactory())->__invoke($this->container);

        static::assertAttributeSame($this->fileWatcher, 'fileWatcher', $reloader);
        static::assertAttributeEquals(new StdoutLogger(), 'logger', $reloader);
        static::assertAttributeSame(500, 'interval', $reloader);
    }

    public function testCreateConfigured(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->container->configure([
            'services' => [
                'config'               => [
                    'mezzio-swoole' => [
                        'hot-code-reload' => [
                            'interval' => 999,
                        ],
                    ],
                ],
                LoggerInterface::class => $logger,
            ],
        ]);
        $reloader = (new ReloaderFactory())->__invoke($this->container);

        static::assertAttributeSame($this->fileWatcher, 'fileWatcher', $reloader);
        static::assertAttributeSame(999, 'interval', $reloader);
        static::assertAttributeSame($logger, 'logger', $reloader);
    }

    /**
     * @psalm-return iterable<array-key, list<array<array-key, array<array-key, array>>>>
     */
    public function provideServiceManagerServicesWithEmptyConfigurations(): iterable
    {
        yield 'empty container' => [
            [],
        ];

        yield 'empty config' => [
            [
                'config' => [],
            ],
        ];

        yield 'empty hot-code-reload' => [
            [
                'config' => [
                    'mezzio-swoole' => [],
                ],
            ],
        ];
    }
}
