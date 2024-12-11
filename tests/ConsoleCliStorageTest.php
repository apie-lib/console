<?php
namespace Apie\Tests\Console;

use Apie\Console\ConsoleCliStorage;
use Apie\Core\Other\MockFileWriter;
use PHPUnit\Framework\TestCase;

class ConsoleCliStorageTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_store_and_restore_keys_in_home_path()
    {
        $filewriter = new MockFileWriter();
        $testItem = new ConsoleCliStorage($filewriter);
        $testKey = 'example';
        $this->assertNull($testItem->restore($testKey));
        $testItem->store($testKey, '42');
        $this->assertCount(1, $filewriter->writtenFiles);
        $testItem = new ConsoleCliStorage($filewriter);
        $this->assertEquals('42', $testItem->restore($testKey));
        $testItem->remove($testKey);
        $this->assertNull($testItem->restore($testKey));
        $this->assertCount(0, $filewriter->writtenFiles);
    }
}