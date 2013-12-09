<?php

namespace Net7\OpenpalApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UtilsControllerTest extends WebTestCase
{

    protected function setUp()
    {
    }

    /**
     * Checks if calling OPTIONS on /letter API the correct status code and documentation is returned
     */
    public function testSwaggerRouting()
    {
        $client = static::createClient();

        $crawle = $client->request('GET', '/swagger/');


        $this->assertTrue( $client->getResponse()->isRedirect() );
        $this->assertRegExp('/swagger\/ui\/index.html/', $client->getResponse()->headers->get('Location'));


        $crawler = $client->request('GET', '/swagger/', array("filename" => "api-docs.json"));

        $this->assertRegExp('/swagger\/api-docs.json/', $client->getResponse()->headers->get('Location'));

    }




}
