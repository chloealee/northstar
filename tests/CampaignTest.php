<?php

use Northstar\Models\User;
use Northstar\Models\Campaign;

class CampaignTest extends TestCase
{

    protected $drupalMock;

    protected $server;
    protected $signedUpServer;
    protected $reportedBackServer;

    /**
     * Migrate database and set up HTTP headers
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        // Migrate & seed database
        Artisan::call('migrate');
        $this->seed();

        // Prepare server headers
        $this->server = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Accept' => 'application/json',
            'HTTP_X-DS-Application-Id' => '456',
            'HTTP_X-DS-REST-API-Key' => 'abc4324',
            'HTTP_Session' => User::find('5430e850dt8hbc541c37tt3d')->login()->key
        );

        $this->signedUpServer = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Accept' => 'application/json',
            'HTTP_X-DS-Application-Id' => '456',
            'HTTP_X-DS-REST-API-Key' => 'abc4324',
            'HTTP_Session' => User::find('5480c950bffebc651c8b456f')->login()->key
        );

        $this->reportedBackServer = array(
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Accept' => 'application/json',
            'HTTP_X-DS-Application-Id' => '456',
            'HTTP_X-DS-REST-API-Key' => 'abc4324',
            'HTTP_Session' => User::find('bf1039b0271bcc636aa5477a')->login()->key
        );

        // Mock Drupal API class
        $this->drupalMock = $this->mock('Northstar\Services\DrupalAPI');
    }


    /**
     * Test for retrieving a user's campaigns
     * GET /users/campaigns
     *
     * @return void
     */
    public function testGetCampaignsFromUser()
    {
        $response = $this->call('GET', 'v1/users/email/test@dosomething.org/campaigns', [], [], [], $this->server);
        $content = $response->getContent();

        // The response should return a 200 OK status code
        $this->assertEquals(200, $response->getStatusCode());

        // Response should be valid JSON
        $this->assertJson($content);
    }


    /**
     * Test for submiting a campaign signup
     * POST /campaigns/:nid/signup
     *
     * @return void
     */
    public function testSubmitCampaignSignup()
    {
        $payload = [
            'source' => 'test'
        ];

        // Mock successful response from Drupal API
        $this->drupalMock->shouldReceive('campaignSignup')->once()->andReturn(100);

        $response = $this->call('POST', 'v1/campaigns/123/signup', [], [], [], $this->server, json_encode($payload));
        $content = $response->getContent();
        $data = json_decode($content, true);

        // The response should return a 201 Created status code
        $this->assertEquals(201, $response->getStatusCode());

        // Response should be valid JSON
        $this->assertJson($content);

        // Response should return created at and sid columns
        $this->assertArrayHasKey('created_at', $data['data']);
        $this->assertArrayHasKey('signup_id', $data['data']);
    }

    /**
     * Test for submitting a new campaign report back.
     * POST /campaigns/:nid/reportback
     *
     * @return void
     */
    public function testSubmitCampaignReportback()
    {
        $payload = [
            'quantity' => 10,
            'why_participated' => 'I love helping others',
            'file' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAMCA',
            'caption' => 'Here I am helping others.'
        ];

        // Mock successful response from Drupal API
        $this->drupalMock->shouldReceive('campaignReportback')->once()->andReturn(100);

        $response = $this->call('POST', 'v1/campaigns/123/reportback', [], [], [], $this->signedUpServer, json_encode($payload));
        $content = $response->getContent();
        $data = json_decode($content, true);

        // The response should return a 201 Created status code
        $this->assertEquals(201, $response->getStatusCode());

        // Response should be valid JSON
        $this->assertJson($content);

        // Response should return created at and rbid columns
        $this->assertArrayHasKey('reportback_id', $data['data']);
        $this->assertArrayHasKey('created_at', $data['data']);
        $this->assertEquals(100, $data['data']['reportback_id']);
    }

    /**
     * Test for successful update of an existing campaign report back.
     * PUT /campaigns/:nid/reportback
     *
     * @return void
     */
    public function testUpdateCampaignReportback200()
    {
        $payload = [
            'quantity' => 10,
            'why_participated' => 'I love helping others',
            'file' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAMCA',
            'caption' => 'Here I am helping others.'
        ];

        // Mock successful response from Drupal API
        $this->drupalMock->shouldReceive('campaignReportback')->once()->andReturn(100);

        $response = $this->call('PUT', 'v1/campaigns/123/reportback', [], [], [], $this->reportedBackServer, json_encode($payload));
        $content = $response->getContent();
        $data = json_decode($content, true);

        // The response should return a 200 Success status code
        $this->assertEquals(200, $response->getStatusCode());

        // Response should be valid JSON
        $this->assertJson($content);

        // Response should return created at and rbid columns
        $this->assertArrayHasKey('reportback_id', $data['data']);
        $this->assertArrayHasKey('created_at', $data['data']);
        $this->assertEquals(100, $data['data']['reportback_id']);
    }

    /**
     * Test for creating a reportback when signup does not exist.
     * PUT /campaigns/:nid/reportback
     *
     * @return void
     */
    public function testUpdateCampaignReportback401()
    {
        $payload = [
            'quantity' => 10,
            'why_participated' => 'I love helping others',
            'file' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAMCA',
            'caption' => 'Here I am helping others.'
        ];

        $response = $this->call('POST', 'v1/campaigns/123/reportback', [], [], [], $this->server, json_encode($payload));
        $content = $response->getContent();

        // Response should return a 501
        $this->assertEquals(401, $response->getStatusCode());

        // Response should be valid JSON
        $this->assertJson($content);
    }
}
