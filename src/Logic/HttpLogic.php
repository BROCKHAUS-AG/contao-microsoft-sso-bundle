<?php

declare(strict_types=1);

/*
 * This file is part of Contao Microsoft SSO Bundle.
 *
 * (c) BROCKHAUS AG 2021 <info@brockhaus-ag.de>
 * Author Niklas Lurse (INoTime) <nlurse@brockhaus-ag.de>
 *
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/BROCKHAUS-AG/contao-microsoft-sso-bundle
 */

namespace BrockhausAg\ContaoMicrosoftSsoBundle\Logic;

class HttpLogic {

    private $oauthCredentials;
    private $groupId;

    public function __construct(array $oauthCredentials, string $groupId) {
        $this->oauthCredentials = $oauthCredentials;
        $this->groupId = $groupId;
    }

    private function createHttpPostRequest($curl, array $headers, array $postParams) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postParams);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("application/x-www-form-urlencoded"));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        return $curl;
    }

    private function createHeaderForTokenRequest(string $postUrl, string $hostname): array
    {
        return array(
            "POST " . $postUrl . " HTTP/1.1",
            "Host: " . $hostname,
            "Content-type: application/x-www-form-urlencoded",
        );
    }

    private function createBodyForTokenRequest(string $clientId, string $clientSecret): array
    {
        return array(
            "client_id" => $clientId,
            "scope" => "https://graph.microsoft.com/.default",
            "client_secret" => $clientSecret,
            "grant_type" => "client_credentials",
        );
    }

    private function getAccessTokenFromJsonResponse($response) : string {
        $jsonResponse = json_decode($response, true);
        return $jsonResponse["access_token"];
    }

    private function makeRequestForToken(string $oauthURL, array $headers, array $postParams) {
        $curl = curl_init($oauthURL);
        $curl = $this->createHttpPostRequest($curl, $headers, $postParams);
        return curl_exec($curl);
    }

    public function getAccessToken() : string {
        $oauthURL = "https://" . $this->oauthCredentials["hostname"]. "/". $this->oauthCredentials["postUrl"];

        $headers = $this->createHeaderForTokenRequest(
            $this->oauthCredentials["postUrl"], $this->oauthCredentials["hostname"]);
        $post_params = $this->createBodyForTokenRequest(
            $this->oauthCredentials["clientId"], $this->oauthCredentials["clientSecret"]);
        $jsonResponse = $this->makeRequestForToken($oauthURL, $headers, $post_params);

        return $this->getAccessTokenFromJsonResponse($jsonResponse);
    }

    private function createHeaderForGroupRequest(string $accessToken) : array
    {
        return array(
            "Authorization: Bearer ". $accessToken
        );
    }

    private function createRequestForGroup(string $groupUrl, string $accessToken) {
        $headers = $this->createHeaderForGroupRequest($accessToken);
        $curl = curl_init($groupUrl);
        curl_setopt($curl, CURLOPT_URL, $groupUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        return curl_exec($curl);
    }

    private function getMembersFromGroupResponse($response) : array
    {
        $jsonResponse = json_decode($response, true);
        return $jsonResponse["value"];
    }

    public function getGroupMembersWithAccessToken(string $accessToken) : array
    {
        $groupUrl = "https://graph.microsoft.com/v1.0/groups/". $this->groupId. "/members";
        $response = $this->createRequestForGroup($groupUrl, $accessToken);
        return $this->getMembersFromGroupResponse($response);
    }
}