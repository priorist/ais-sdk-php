<?php

use PHPUnit\Framework\TestCase;

use Priorist\AIS\Client\Client;
use Priorist\AIS\Client\Collection;
use Priorist\AIS\Client\Rest\ClientException;

class EventTest extends TestCase
{
    public function testUpcoming()
    {
        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        $events = $client->event->findUpcoming();

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertGreaterThanOrEqual(0, $events->count());

        if (!$events->hasItems()) {
            $this->markTestSkipped('No events returned.');
        }

        foreach ($events as $event) {
            $this->assertIsArray($event);
            $this->assertIsInt($event['id']);
        }

        $this->assertNull($events->current());

        $events->rewind();

        return $events;
    }


    /**
     * @depends testUpcoming
     */
    public function testSingle(Collection $events)
    {
        $this->assertIsArray($events->current());
        $this->assertArrayHasKey('id', $events->current());

        $existingEventId = $events->current()['id'];

        $this->assertIsInt($existingEventId);

        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        $this->assertNull($client->event->findById(0));

        $event = $client->event->findById($existingEventId);

        $this->assertIsArray($event);
        $this->assertArrayHasKey('id', $event);
        $this->assertEquals($existingEventId, $event['id']);

        $this->assertArrayHasKey('event_base', $event);
        $this->assertIsArray($event['event_base']);

        return $event;
    }


    /**
     * @depends testSingle
     */
    public function testEnrollment(array $event)
    {
        $this->assertArrayHasKey('prices', $event);
        $this->assertIsArray($event['prices']);
        $this->assertGreaterThan(0, count($event['prices']));

        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        $requestedEnrollment = [
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'event'         => $event['id'],
            'price'         => $event['prices'][0]['id'],
        ];

        $confirmedEnrollment = $client->enrollment->create($requestedEnrollment);

        $this->assertIsArray($confirmedEnrollment);
        $this->assertArrayHasKey('id', $confirmedEnrollment);
        $this->assertArrayHasKey('event', $confirmedEnrollment);
        $this->assertArrayHasKey('price', $confirmedEnrollment);

        $this->assertEquals($requestedEnrollment['event'], $confirmedEnrollment['event']);
        $this->assertEquals($requestedEnrollment['price'], $confirmedEnrollment['price']);

        return $requestedEnrollment;
    }


    /**
     * @depends testEnrollment
     */
    public function testInvalidEnrollment(array $enrollment)
    {
        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        unset($enrollment['first_name']);

        $clientException = null;

        try {
            $client->enrollment->create($enrollment);
        } catch (ClientException $e) {
            $clientException = $e;
        }

        $this->assertInstanceOf(ClientException::class, $clientException);
        $this->assertIsArray($clientException->getDetails());
        $this->assertGreaterThan(0, count($clientException->getDetails()));
    }


    /**
     * @depends testSingle
     */
    public function testByCategory(array $event)
    {
        $this->assertArrayHasKey('categories', $event['event_base']);
        $this->assertIsArray($event['event_base']['categories']);
        $this->assertIsInt($event['event_base']['categories'][0]);

        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        $events = $client->event->findUpcomingByCategory($event['event_base']['categories'][0]);

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertGreaterThan(0, $events->count());

        $events = $client->event->findUpcomingByCategories($event['event_base']['categories']);

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertGreaterThan(0, $events->count());
    }


    /**
     * @depends testSingle
     */
    public function testSearch(array $event)
    {
        $client = new Client(getenv('AIS_URL'), getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));

        $events = $client->event->findBySearchPhrase($event['meta']['event_base_name']);

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertGreaterThan(0, $events->count());
    }
}
