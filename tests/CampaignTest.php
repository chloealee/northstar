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
     * GET /users/:term/:id/campaigns
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
     * POST /user/campaigns/:nid/signup
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

        $response = $this->call('POST', 'v1/user/campaigns/123/signup', [], [], [], $this->server, json_encode($payload));
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
     * Test for forwarding a campaign signup from phoenix -> northstar
     * POST /user/campaigns/:nid/signup
     *
     * @return void
     */
    public function testForwardedCampaignSignup()
    {
        // payload with source & signup_id
        $payload = [
            'source' => 'test',
            'signup_id' => '100',
        ];

        // use key that has `admin` scope

        // assert response is 201 and has `signup_id` field
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertArrayHasKey('signup_id', $data['data']);

        $this->assertTrue(false);

    }

    /**
     * Test that admin scope is required to complete forwarded signup
     * POST /user/campaigns/:nid/signup
     *
     * @return void
     */
    public function testForwardedCampaignSignupRequiresAdminScope()
    {
        // payload with source & signup_id
        $payload = [
            'source' => 'test',
            'signup_id' => '100',
        ];

        // use that key that has `normal` scope

        // asset response throws a 403 error
        $this->assertEquals(201, $response->getStatusCode());

        $this->assertTrue(false);
    }

    /**
     * Test for submitting a duplicate campaign signup
     * POST /user/campaigns/:nid/signup
     *
     * @return void
     */
    public function testDuplicateCampaignSignup()
    {
        $payload = ['source' => 'test'];

        $response = $this->call('POST', 'v1/user/campaigns/123/signup', [], [], [], $this->signedUpServer, json_encode($payload));
        $content = $response->getContent();
        $data = json_decode($content, true);

        // Verify a 200 status code
        $this->assertEquals(200, $response->getStatusCode());

        // Verify the signup_id is the same as what was already there
        $this->assertEquals(100, $data['data']['signup_id']);
    }

    /**
     * Test for submitting a new campaign report back.
     * POST /user/campaigns/:nid/reportback
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

        $response = $this->call('POST', 'v1/user/campaigns/123/reportback', [], [], [], $this->signedUpServer, json_encode($payload));
        $content = $response->getContent();
        $data = json_decode($content, true);

        // The response should return a 201 Created status code
        $this->assertEquals(201, $response->getStatusCode());

        // Response should be valid JSON
        $this->assertJson($content);

        // Response should return created at and rbid columns
        $this->assertArrayHasKey('reportback_id', $data['data']);
        $this->assertEquals(100, $data['data']['reportback_id']);
    }

    /**
     * Test for successful update of an existing campaign report back.
     * PUT /user/campaigns/:nid/reportback
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

        $response = $this->call('PUT', 'v1/user/campaigns/123/reportback', [], [], [], $this->reportedBackServer, json_encode($payload));
        $content = $response->getContent();
        $data = json_decode($content, true);

        // The response should return a 200 Success status code
        $this->assertEquals(200, $response->getStatusCode());

        // Response should be valid JSON
        $this->assertJson($content);

        // Response should return created at and rbid columns
        $this->assertArrayHasKey('reportback_id', $data['data']);
        $this->assertEquals(100, $data['data']['reportback_id']);
    }

    /**
     * Test for creating a reportback when signup does not exist.
     * PUT /user/campaigns/:nid/reportback
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

        $response = $this->call('POST', 'v1/user/campaigns/123/reportback', [], [], [], $this->server, json_encode($payload));
        $content = $response->getContent();

        // Response should return a 501
        $this->assertEquals(401, $response->getStatusCode());

        // Response should be valid JSON
        $this->assertJson($content);
    }
}
