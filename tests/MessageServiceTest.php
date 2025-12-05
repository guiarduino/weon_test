<?php

namespace Tests;

use App\Models\Message;
use Tests\TestCase;
use App\Services\MessageService;
use App\Repository\MessageRepository;
use Illuminate\Validation\ValidationException;
use Mockery;

class MessageServiceTest extends TestCase
{
    protected $repository;
    protected MessageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(MessageRepository::class);
        $this->service = new MessageService($this->repository);
    }

    public function test_it_stores_an_incoming_text_message()
    {
        $number = "5511999999999";

        $payload = [
            "type" => "message",
            "timestamp" => 123123123,
            "payload" => [
                "id" => "MSG-123",
                "type" => "text",
                "source" => "55999999999",
                "payload" => [
                    "text" => "Olá mundo"
                ]
            ]
        ];

        $this->repository
            ->shouldReceive("createOrUpdate")
            ->once()
            ->andReturn(new Message(['id' => 10]));

        $result = $this->service->storeIncoming($number, $payload);

        $this->assertEquals("message", $result["type"]);
        $this->assertEquals("text", $result["event_type"]);
        $this->assertEquals(10, $result["data"]["database_id"]);
        $this->assertEquals("incoming_message", $result["data"]["action"]);
    }

    /** @test */
    public function it_stores_an_enqueued_event()
    {
        $number = "5511987654321";

        $payload = [
            "type" => "message-event",
            "timestamp" => 999999,
            "payload" => [
                "id" => "EVT-123",
                "type" => "enqueued",
                "destination" => "5511998877665",
                "payload" => [
                    "whatsappMessageId" => "WPP-01",
                    "type" => "session"
                ]
            ]
        ];

        $this->repository
            ->shouldReceive("createOrUpdate")
            ->once()
            ->andReturn((object)["id" => 50]);

        $result = $this->service->storeIncoming($number, $payload);

        $this->assertEquals("message-event", $result["type"]);
        $this->assertEquals("enqueued", $result["event_type"]);
        $this->assertEquals("enqueued", $result["data"]["action"]);
        $this->assertEquals(50, $result["data"]["database_id"]);
    }

    /** @test */
    public function it_updates_message_to_read()
    {
        $number = "5511988888888";

        $payload = [
            "type" => "message-event",
            "payload" => [
                "type" => "read",
                "gsId" => "GS-555",
                "payload" => [
                    "ts" => 123456
                ],
            ]
        ];

        $fakeMessage = (object)[
            "id" => 99,
            "body" => json_encode(["foo" => "bar"])
        ];

        $this->repository
            ->shouldReceive("findByProviderId")
            ->once()
            ->with("GS-555")
            ->andReturn($fakeMessage);

        $this->repository
            ->shouldReceive("update")
            ->once()
            ->andReturn(true);

        $result = $this->service->storeIncoming($number, $payload);

        $this->assertEquals("read", $result["event_type"]);
        $this->assertEquals(99, $result["data"]["database_id"]);
    }

    /** @test */
    public function it_updates_message_to_failed()
    {
        $number = "551199999999";

        $payload = [
            "type" => "message-event",
            "payload" => [
                "type" => "failed",
                "gsId" => "GS-FAIL-1",
                "payload" => [
                    "code" => "ERR001",
                    "reason" => "Blocked"
                ]
            ]
        ];

        $fakeMessage = (object)[
            "id" => 70,
            "body" => "{}"
        ];

        $this->repository
            ->shouldReceive("findByProviderId")
            ->once()
            ->with("GS-FAIL-1")
            ->andReturn($fakeMessage);

        $this->repository
            ->shouldReceive("update")
            ->once()
            ->andReturn(true);

        $result = $this->service->storeIncoming($number, $payload);

        $this->assertEquals("failed", $result["data"]["action"]);
        $this->assertEquals(70, $result["data"]["database_id"]);
    }

    /** @test */
    public function it_throws_validation_error_on_invalid_payload()
    {
        $this->expectException(ValidationException::class);

        $this->service->storeIncoming("5511999999999", [
            "type" => "message", // faltando vários campos obrigatórios
            "payload" => []
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
