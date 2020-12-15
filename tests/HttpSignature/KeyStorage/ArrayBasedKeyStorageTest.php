<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\KeyStorage;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\KeyStorage\ArrayBasedKeyStorage;

class ArrayBasedKeyStorageTest extends TestCase
{
    /**
     * @param array $data
     *
     * @dataProvider storageDataProvider
     */
    public function testFetch(array $data)
    {
        $copiedData = $data; // if the storage actually taken data as reference and changes it in the runtime.
        $storage = new ArrayBasedKeyStorage($copiedData);

        foreach ($data as $id => $_) {
            $this->assertSame($data[$id], $storage->fetch($id));
        }
    }

    /**
     * @param array $data
     *
     * @dataProvider storageDataProvider
     */
    public function testFetchNull(array $data)
    {
        $copiedData = $data; // if the storage actually taken data as reference and changes it in the runtime.
        $storage = new ArrayBasedKeyStorage($copiedData);

        $this->assertNull($storage->fetch('unknown id'));
    }

    public function storageDataProvider()
    {
        return [
            'data is string' => [['id1' => 'my amazing key data']],
            'data is int' => [['id1' => 100]],
            'data is float' => [['id1' => 99.5]],
            'data is object' => [['id1' => new \stdClass()]],
            'data is array' => [['id1' => ['not empty array']]],
            'data is callback' => [
                [
                    'id1' => function () {
                        return false;
                    },
                ],
            ],
            'different values' => [['id1' => 'some string', 'other_key' => new \stdClass()]],
        ];
    }
}
