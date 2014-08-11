<?php

namespace Ice\Features\Context;
use Behat\Behat\Exception\PendingException;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\MinkContext;
use Goutte\Client;
use WebDriver\Exception\WebTestAssertion;

class ClientContext extends MinkContext
{
    /**
     * @Given /^my client accepts content type "([^"]*)"$/
     */
    public function myClientAccepts($accepts)
    {
        $this->getSession()->setRequestHeader('Accept', $accepts);
    }

    /**
     * @Given /^I am connecting with HTTPS$/
     */
    public function iAmConnectingWithHttps()
    {
        $this->getSession()->setRequestHeader('X-Forwarded-Proto', 'https');
    }

    /**
     * @Given /^I use the username "([^"]*)" with password "([^"]*)"$/
     */
    public function iUseTheUsernameWithPassword($username, $password)
    {
        $this->getSession()->setBasicAuth($username, $password);
    }

    /**
     * @When /^I post "([^"]*)" as "([^"]*)" to "([^"]*)"$/
     */
    public function iPostAsTo($path, $requestFormat, $uri)
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $path;

        if (!file_exists($filePath)) {
            throw new PendingException($path." does not exist.");
        }

        $driver = $this->getSession()->getDriver();

        if (!$driver instanceof BrowserKitDriver) {
            throw new PendingException("POST requests are currently only supported when using the BrowserKit driver");
        }

        switch ($requestFormat) {
            case 'json':
                $driver->getClient()->request(
                    'POST', $this->locatePath($uri), array(), array(), array('HTTP_CONTENT_TYPE' => 'application/json'),
                    file_get_contents($filePath)
                );

                return;
            default:
                throw new PendingException("Request format '".$requestFormat."' has not been defined");
        }
    }

    /**
     * @Given /^the response JSON should contain the field "([^"]*)" with value "([^"]*)"$/
     */
    public function theResponseJsonShouldContainTheFieldWithValue($field, $value)
    {
        $rawResponseBody = $this->getSession()->getPage()->getContent();
        $responseData = json_decode($rawResponseBody, true);

        if ($responseData === null) {
            throw new WebTestAssertion("The response body is not json:\n".$rawResponseBody);
        }

        if (!array_key_exists($field, $responseData)) {
            throw new WebTestAssertion(sprintf("The field '%s' is not present in the response", $field));
        }

        if ($responseData[$field] != $value) {
            throw new WebTestAssertion(sprintf("Field '%s' has value '%s', expected '%s'", $field, $responseData[$field], $value));
        }
    }

    /**
     * @Given /^the response JSON should match "([^"]*)"$/
     */
    public function responseJsonShouldMatch($path)
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $path;

        if (!file_exists($filePath)) {
            throw new PendingException();
        }

        $content = file_get_contents($filePath);
        $pageContent = $this->getSession()->getPage()->getContent();

        if (!$this->isJsonEqual($content, $pageContent)) {
            throw new WebTestAssertion("Content is " . $pageContent);
        }
    }

    /**
     * @Given /^the response JSON should contain "([^"]*)"$/
     */
    public function responseJsonShouldContain($path)
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $path;

        if (!file_exists($filePath)) {
            throw new PendingException();
        }

        $content = file_get_contents($filePath);
        $pageContent = $this->getSession()->getPage()->getContent();

        $diff = $this->arrayDiff(json_decode($content, true), json_decode($pageContent, true));

        if (count($diff) != 0) {
            throw new WebTestAssertion("Response differs at " . json_encode($diff));
        }
    }

    protected function isJsonEqual($string1, $string2)
    {
        $json1 = json_decode($string1, true);
        $json2 = json_decode($string2, true);

        $difference = array_merge($this->arrayDiff($json1, $json2), $this->arrayDiff($json2, $json1));

        return count($difference) == 0;
    }

    protected function arrayDiff($array1, $array2)
    {
        $difference = array();
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->arrayDiff($value, $array2[$key]);
                    if (!empty($new_diff))
                        $difference[$key] = $new_diff;
                }
            } else if (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }
}
