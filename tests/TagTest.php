<?php

use PHPUnit\Framework\TestCase;

use Priorist\AIS\Client\Client;
use Priorist\AIS\Client\Collection;


class TagTest extends TestCase
{
    public function testList()
    {
        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        $tags = $client->tag->findAll();

        $this->assertInstanceOf(Collection::class, $tags);
        $this->assertGreaterThanOrEqual(0, $tags->count());

        if (!$tags->hasItems()) {
            $this->markTestSkipped('No tags returned.');
        }

        foreach ($tags as $tag) {
            $this->assertIsArray($tag);
            $this->assertIsInt($tag['id']);
        }

        $this->assertNull($tags->current());

        $tags->rewind();

        return $tags;
    }


    /**
     * @depends testList
     */
    public function testSingle(Collection $tags)
    {
        $this->assertIsArray($tags->current());
        $this->assertArrayHasKey('id', $tags->current());

        $existingTagId = $tags->current()['id'];

        $this->assertIsInt($existingTagId);

        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        $this->assertNull($client->tag->findById(0));

        $tag = $client->tag->findById($existingTagId);

        $this->assertIsArray($tag);
        $this->assertArrayHasKey('id', $tag);
        $this->assertEquals($existingTagId, $tag['id']);

        $this->assertArrayHasKey('name', $tag);

        return $tag;
    }


    /**
     * @depends testSingle
     */
    public function testSearch(array $tag)
    {
        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        $tags = $client->tag->findBySearchPhrase($tag['name']);

        $this->assertInstanceOf(Collection::class, $tags);
        $this->assertGreaterThan(0, $tags->count());
    }
}
