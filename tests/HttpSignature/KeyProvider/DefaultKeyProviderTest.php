<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\KeyProvider;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Exception\KeyNotFoundException;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\KeyLoader\KeyLoaderInterface;
use Yandex\Eats\HttpSignature\KeyProvider\DefaultKeyProvider;
use Yandex\Eats\HttpSignature\KeyStorage\KeyStorageInterface;

class DefaultKeyProviderTest extends TestCase
{
    /**
     * @var KeyStorageInterface|MockObject
     */
    private $storageMock;

    /**
     * @var KeyLoaderInterface|MockObject
     */
    private $skippedLoaderMock;

    /**
     * @var KeyLoaderInterface|MockObject
     */
    private $usedLoaderMock;

    protected function setUp()
    {
        parent::setUp();

        $this->storageMock = $this->getMockBuilder(KeyStorageInterface::class)
            ->setMethods(['fetch'])
            ->getMock();

        $this->usedLoaderMock = $this->getMockBuilder(KeyLoaderInterface::class)
            ->setMethods(['load', 'isSupports'])
            ->getMock();

        $this->skippedLoaderMock = $this->getMockBuilder(KeyLoaderInterface::class)
            ->setMethods(['load', 'isSupports'])
            ->getMock();
    }

    /**
     * @param $raw
     *
     * @dataProvider rawKeysDataProvider
     */
    public function testFetch($raw)
    {
        $id = uniqid();
        $key = $this->getMockBuilder(KeyInterface::class)->getMock();

        $this->storageMock
            ->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willReturn($raw);

        $this->usedLoaderMock
            ->expects($this->once())
            ->method('isSupports')
            ->with($raw)
            ->willReturn(true);

        $this->usedLoaderMock
            ->expects($this->once())
            ->method('load')
            ->with($id, $raw)
            ->willReturn($key);

        $provider = new DefaultKeyProvider($this->storageMock, [$this->usedLoaderMock]);

        $this->assertSame($key, $provider->fetch($id));
    }

    /**
     * @param $raw
     *
     * @dataProvider rawKeysDataProvider
     */
    public function testFetchWithSkippedLoader($raw)
    {
        $id = uniqid();
        $key = $this->getMockBuilder(KeyInterface::class)->getMock();

        $this->storageMock
            ->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willReturn($raw);

        $this->skippedLoaderMock
            ->expects($this->once())
            ->method('isSupports')
            ->with($raw)
            ->willReturn(false);

        $this->skippedLoaderMock
            ->expects($this->never())
            ->method('load');

        $this->usedLoaderMock
            ->expects($this->once())
            ->method('isSupports')
            ->with($raw)
            ->willReturn(true);

        $this->usedLoaderMock
            ->expects($this->once())
            ->method('load')
            ->with($id, $raw)
            ->willReturn($key);

        $provider = new DefaultKeyProvider($this->storageMock, [$this->skippedLoaderMock, $this->usedLoaderMock]);

        $this->assertSame($key, $provider->fetch($id));
    }

    public function testKeyNotFound()
    {
        $id = uniqid();

        $this->storageMock
            ->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willReturn(null);

        $provider = new DefaultKeyProvider($this->storageMock, []);

        $this->expectException(KeyNotFoundException::class);
        $provider->fetch($id);
    }

    /**
     * @param $raw
     * @dataProvider rawKeysDataProvider
     */
    public function testKeyNotMatched($raw)
    {
        $id = uniqid();

        $this->storageMock
            ->expects($this->once())
            ->method('fetch')
            ->with($id)
            ->willReturn($raw);

        $this->skippedLoaderMock
            ->expects($this->once())
            ->method('isSupports')
            ->with($raw)
            ->willReturn(false);

        $this->skippedLoaderMock
            ->expects($this->never())
            ->method('load');

        $provider = new DefaultKeyProvider($this->storageMock, [$this->skippedLoaderMock]);

        $this->expectException(KeyNotMatchException::class);
        $provider->fetch($id);
    }

    public function rawKeysDataProvider()
    {
        return [
            'string key' => ['some key'],
            'int key' => [42],
            'float key' => [65.1],
            'array key' => [['this is some array']],
            'object key' => [new \stdClass()],
            'callable key' => [
                function () {
                    return 'hello!';
                },
            ],
        ];
    }
}
