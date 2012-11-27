<?php

namespace Ice\ExternalUserBundle\Tests\Controller;

use Ice\ExternalUserBundle\Tests\Util\WebTestCase;

class UsersControllerTest extends WebTestCase
{
    public function setUp()
    {
        $classes = array(
            'Ice\ExternalUserBundle\DataFixtures\ORM\LoadUserData',
        );

        $this->loadFixtures($classes);
    }

    public function testGetUsersAction()
    {
        $client = $this->getJsonClient();
        $crawler = $client->request('GET', '/api/users', array(), array(), array());

        $response = $client->getResponse();
        $this->assertJsonResponse($response, 200);
    }

    public function testGetUserAction()
    {
        $client = $this->getJsonClient();
        $crawler = $client->request('GET', '/api/users/abc12');

        $response = $client->getResponse();
        $this->assertJsonResponse($response);

        // Check content decoded correctly
        $content = $response->getContent();
        json_decode($content);
        $this->assertEmpty(json_last_error());
    }

    public function testPostUsersAction()
    {
        $client = $this->getJsonClient();
        $crawler = $client->request('POST', '/api/users', array(), array(), array(), $this->createCompleteJsonRegisterBody());

        $response = $client->getResponse();
        $this->assertJsonResponse($response, 201);
    }

    public function testGetUsersAuthenticateAction()
    {
        $client = $this->getJsonClient();
        $client->setServerParameter('PHP_AUTH_USER', 'abc12');
        $client->setServerParameter('PHP_AUTH_PW', 'password');

        $crawler = $client->request('GET', '/api/users/authenticate');

        $response = $client->getResponse();
        $this->assertJsonResponse($response, 302);

        $client->followRedirect();
        $response = $client->getResponse();
        $this->assertJsonResponse($response);
    }

    protected function createCompleteJsonRegisterBody()
    {
        $body = array(
            "plainPassword" => "password",
            "email" => "test11222621@blah.com",
            "title" => "Mr",
            "firstNames" => "First Names",
            "lastName" => "Lastname",
            "dob" => "1900-01-01",
        );

        return json_encode($body);
    }

    protected function createIncompleteJsonRegisterBody()
    {
        $body = array(
            "plainPassword" => "password",
            "email" => "test@blah.com",
        );

        return json_encode($body);
    }
}