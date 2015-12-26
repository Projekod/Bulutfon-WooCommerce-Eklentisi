<?php
namespace Bulutfon\OAuth2\Client\Provider;

use Bulutfon\OAuth2\Client\Entity\Announcement;
use Bulutfon\OAuth2\Client\Entity\AutomaticCall;
use Bulutfon\OAuth2\Client\Entity\AutomaticCallRecipient;
use Bulutfon\OAuth2\Client\Entity\CallFlow;
use Bulutfon\OAuth2\Client\Entity\CdrObject;
use Bulutfon\OAuth2\Client\Entity\Did;
use Bulutfon\OAuth2\Client\Entity\Extension;
use Bulutfon\OAuth2\Client\Entity\Group;
use Bulutfon\OAuth2\Client\Entity\IncomingFax;
use Bulutfon\OAuth2\Client\Entity\Message;
use Bulutfon\OAuth2\Client\Entity\MessageRecipient;
use Bulutfon\OAuth2\Client\Entity\MessageTitle;
use Bulutfon\OAuth2\Client\Entity\Origination;
use Bulutfon\OAuth2\Client\Entity\OutgoingFax;
use Bulutfon\OAuth2\Client\Entity\OutgoingFaxRecipient;
use Bulutfon\OAuth2\Client\Entity\TokenInfo;
use Bulutfon\OAuth2\Client\Entity\User;
use Bulutfon\OAuth2\Client\Entity\Cdr;
use Bulutfon\OAuth2\Client\Entity\WorkingHour;
use Guzzle\Http\Exception\BadResponseException;
use League\OAuth2\Client\Exception\IDPException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\AbstractProvider;

class Bulutfon extends AbstractProvider
{
    public $scopes = ['cdr'];
    public $uidKey = 'user_id';
    public $responseType = 'json';

    public $baseUrl = "https://api.bulutfon.com";
    public $authUrl = "https://app.bulutfon.com/oauth/authorize";
    public $tokenUrl = "https://app.bulutfon.com/oauth/token";

    public $verifySSL = true;

    public function getHttpClient()
    {
        $client = clone $this->httpClient;
        $client->setSslVerification($this->verifySSL);
        return $client;
    }

    public function urlAuthorize()
    {
        return $this->authUrl;
    }

    public function urlAccessToken()
    {
        return $this->tokenUrl;
    }

    public function getAccessToken($grant = 'authorization_code', $params = [])
    {
        if (is_string($grant)) {
            // PascalCase the grant. E.g: 'authorization_code' becomes 'AuthorizationCode'
            $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $grant)));
            $grant = 'League\\OAuth2\\Client\\Grant\\' . $className;
            if (!class_exists($grant)) {
                throw new \InvalidArgumentException('Unknown grant "' . $grant . '"');
            }
            $grant = new $grant();
        } elseif (!$grant instanceof GrantInterface) {
            $message = get_class($grant) . ' is not an instance of League\OAuth2\Client\Grant\GrantInterface';
            throw new \InvalidArgumentException($message);
        }
        $defaultParams = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => $grant,
        ];
        $requestParams = $grant->prepRequestParams($defaultParams, $params);
        try {
            switch (strtoupper($this->method)) {
                case 'GET':
                    // @codeCoverageIgnoreStart
                    // No providers included with this library use get but 3rd parties may
                    $client = $this->getHttpClient();
                    $client->setBaseUrl($this->urlAccessToken() . '?' . $this->httpBuildQuery($requestParams, '', '&'));
                    $request = $client->get(null, null, $requestParams)->send();
                    $response = $request->getBody();
                    break;
                // @codeCoverageIgnoreEnd
                case 'POST':
                    $client = $this->getHttpClient();
                    $client->setBaseUrl($this->urlAccessToken());
                    $request = $client->post(null, null, $requestParams)->send();
                    $response = $request->getBody();
                    break;
                // @codeCoverageIgnoreStart
                default:
                    throw new \InvalidArgumentException('Neither GET nor POST is specified for request');
                // @codeCoverageIgnoreEnd
            }
        } catch (BadResponseException $e) {
            // @codeCoverageIgnoreStart
            $response = $e->getResponse()->getBody();
            // @codeCoverageIgnoreEnd
        }
        switch ($this->responseType) {
            case 'json':
                $result = json_decode($response, true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $result = [];
                }
                break;
            case 'string':
                parse_str($response, $result);
                break;
        }
        if (isset($result['error']) && !empty($result['error'])) {
            // @codeCoverageIgnoreStart
            throw new IDPException($result);
            // @codeCoverageIgnoreEnd
        }
        $result = $this->prepareAccessTokenResult($result);
        $accessToken = $grant->handleResponse($result);
        // Add email from response
        if (!empty($result['email'])) {
            $accessToken->email = $result['email'];
        }
        return $accessToken;
    }

    public function fetchProviderData($url, array $headers = [])
    {
        try {
            $client = $this->getHttpClient();
            $client->setBaseUrl($url);

            if ($headers) {
                $client->setDefaultOption('headers', $headers);
            }

            $request = $client->get()->send();
            $response = $request->getBody();
        } catch (BadResponseException $e) {
            // @codeCoverageIgnoreStart
            $raw_response = explode("\n", $e->getResponse());
            $response = $e->getResponse()->getBody();
            $response = json_decode($response);

            if($response && $response->error == 'Token expired') {
                $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                header("Location: ". $this->redirectUri ."?refresh_token=true&back=".$actual_link);
            } else {
                throw new IDPException(end($raw_response));
            }
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    public function postProviderData($url, $params, array $headers = [])
    {
        try {
            $client = $this->getHttpClient();
            $client->setBaseUrl($url);

            if ($headers) {
                $client->setDefaultOption('headers', $headers);
            }

            $request = $client->post($url,array(
                'content-type' => 'application/json'
            ),array());
            $request->setBody(json_encode($params)); #set body!
            $request = $request->send();
            $response = $request->getBody();
        } catch (BadResponseException $e) {
            // @codeCoverageIgnoreStart
            $raw_response = explode("\n", $e->getResponse());
            $response = $e->getResponse()->getBody();
            $response = json_decode($response);

            if($response && $response->error == 'Token expired') {
                $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                header("Location: ". $this->redirectUri ."?refresh_token=true&back=".$actual_link);
            }
            throw new IDPException(end($raw_response));
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    public function putProviderData($url, $params, array $headers = [])
    {
        try {
            $client = $this->getHttpClient();
            $client->setBaseUrl($url);

            if ($headers) {
                $client->setDefaultOption('headers', $headers);
            }

            $request = $client->put($url,array(
                'content-type' => 'application/json'
            ),array());
            $request->setBody(json_encode($params)); #set body!
            $request = $request->send();
            $response = $request->getBody();
        } catch (BadResponseException $e) {
            // @codeCoverageIgnoreStart
            $raw_response = explode("\n", $e->getResponse());
            $response = $e->getResponse()->getBody();
            $response = json_decode($response);

            if($response && $response->error == 'Token expired') {
                $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                header("Location: ". $this->redirectUri ."?refresh_token=true&back=".$actual_link);
            }
            throw new IDPException(end($raw_response));
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    public function deleteProviderData($url, array $headers = [])
    {
        try {
            $client = $this->getHttpClient();
            $client->setBaseUrl($url);

            if ($headers) {
                $client->setDefaultOption('headers', $headers);
            }
            $request = $client->delete()->send();
            $response = $request->getBody();
        } catch (BadResponseException $e) {
            // @codeCoverageIgnoreStart
            $raw_response = explode("\n", $e->getResponse());
            $response = $e->getResponse()->getBody();
            $response = json_decode($response);

            if($response && $response->error == 'Token expired') {
                $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                header("Location: ". $this->redirectUri ."?refresh_token=true&back=".$actual_link);
            }
            throw new IDPException(end($raw_response));
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }


    function getFile($fromUrl, $toFile) {
        try {
            $client = $this->getHttpClient();
            $response = $client->get($fromUrl)
                ->setResponseBody($toFile)
                ->send();
            return true;
        } catch (Exception $e) {
            // Log the error or something
            return false;
        }
    }

    /* USER METHODS */

    public function urlUserDetails(AccessToken $token)
    {
        return $this->baseUrl."/me?access_token=".$token;
    }
    public function userDetails($response, AccessToken $token)
    {
        $user = new User();
        $email = (isset($token->email)) ? $token->email : null;
        $location = (isset($response->country)) ? $response->country : null;
        $description = (isset($response->status)) ? $response->status : null;
        $user->exchangeArray([
            'user' => $response->user,
            'pbx' => $response->pbx,
            'credit' => $response->credit,
        ]);
        return $user;
    }
    public function userUid($response, AccessToken $token)
    {
        return $response->user->email;
    }
    public function userEmail($response, AccessToken $token)
    {
        return (isset($token->email)) ? $token->email : null;
    }
    public function userScreenName($response, AccessToken $token)
    {
        return $response->name;
    }

    /* DID METHODS */

    protected function urlDid(AccessToken $token, $id = null)
    {
        $url = "";
        if($id) {
            $url = $this->baseUrl."/dids/". $id ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/dids?access_token=".$token;
        }
        return $url;
    }

    protected function fetchDids(AccessToken $token, $id = null)
    {
        $url = $this->urlDid($token, $id);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function workingHours($working_hours)
    {
        $working_hour = new WorkingHour();
        $working_hour->exchangeArray([
            'monday' => $working_hours->monday,
            'tuesday' => $working_hours->tuesday,
            'wednesday' => $working_hours->wednesday,
            'thursday' => $working_hours->thursday,
            'friday' => $working_hours->friday,
            'saturday' => $working_hours->saturday,
            'sunday' => $working_hours->sunday,
        ]);

        return $working_hour;
    }

    protected function did($response, $id = null)
    {
        $did = new Did();
        $did->exchangeArray([
            'id' => $response->id,
            'number' => $response->number,
            'state' => $response->state,
            'destination_type' => $response->destination_type,
            'destination_id' => $response->destination_id,
            'destination_number' => $response->destination_number,
            'working_hour' => $response->working_hour,
            'working_hours' => ($response->working_hour && $id) ? $this->workingHours($response->working_hours) : null,
        ]);

        return $did;
    }
    protected function dids($response, AccessToken $token, $id = null) {
        if($id) {
            return $this->did($response->did, $id);
        } else {
            $dids = array();
            $response_dids = $response->dids;
            foreach($response_dids as $response_did) {
                $did = $this->did($response_did);
                array_push($dids, $did);
            }

            return $dids;
        }
    }

    public function getDids(AccessToken $token) {
        $response = $this->fetchDids($token);
        return $this->dids(json_decode($response), $token);
    }

    public function getDid(AccessToken $token, $id) {
        $response = $this->fetchDids($token, $id);
        return $this->dids(json_decode($response), $token, $id);
    }

    /* EXTENSION METHODS */

    protected function urlExtension(AccessToken $token, $id = null)
    {
        $url = "";
        if($id) {
            $url = $this->baseUrl."/extensions/". $id ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/extensions?access_token=".$token;
        }
        return $url;
    }

    protected function fetchExtensions(AccessToken $token, $id = null)
    {
        $url = $this->urlExtension($token, $id);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function extension($response, $id = null)
    {
        $extension = new Extension();
        $extension->exchangeArray([
            'id' => $response->id,
            'number' => $response->number,
            'registered' => property_exists($response, "registered") ? $response->registered : null,
            'caller_name' => $response->caller_name,
            'email' => $response->email,
            'did' => $id ? $response->did : null,
            'voice_mail' => property_exists($response, "voice_mail") ? $response->voice_mail : null,
            'redirection_type' => property_exists($response, "redirection_type") ? $response->redirection_type : null,
            'destination_type' => property_exists($response, "destination_type") ? $response->destination_type : null,
            'destination_number' => property_exists($response, "destination_number") ? $response->destination_number : null,
            'external_number' => property_exists($response, "external_number") ? $response->external_number : null,
            'acl' => $id ? $response->acl : null,
        ]);

        return $extension;
    }
    protected function extensions($response, AccessToken $token, $id = null) {
        if($id) {
            return $this->extension($response->extension, $id);
        } else {
            $extensions = array();
            $response_extensions = $response->extensions;
            foreach($response_extensions as $response_extension) {
                $extension = $this->extension($response_extension);

                array_push($extensions, $extension);

            }

            return $extensions;
        }
    }

    public function getExtensions(AccessToken $token) {
        $response = $this->fetchExtensions($token);
        return $this->extensions(json_decode($response), $token);
    }

    public function getExtension(AccessToken $token, $id) {
        $response = $this->fetchExtensions($token, $id);
        return $this->extensions(json_decode($response), $token, $id);
    }

    public function createExtension(AccessToken $token, $params) {
        $url = $this->urlExtension($token);
        $response = $this->postProviderData($url, $params);
        return json_decode($response);
    }

    public function updateExtension(AccessToken $token, $id, $params) {
        $url = $this->urlExtension($token, $id);
        $response = $this->putProviderData($url, $params);
        return json_decode($response);
    }

    public function deleteExtension(AccessToken $token, $id) {
        $url = $this->urlExtension($token, $id);
        $response = $this->deleteProviderData($url);
        return json_decode($response);
    }

    /* GROUP METHODS */

    protected function urlGroup(AccessToken $token, $id = null)
    {
        $url = "";
        if($id) {
            $url = $this->baseUrl."/groups/". $id ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/groups?access_token=".$token;
        }
        return $url;
    }

    protected function fetchGroups(AccessToken $token, $id = null)
    {
        $url = $this->urlGroup($token, $id);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function group($response, AccessToken $token, $id = null)
    {
        $group = new Group();
        $group->exchangeArray([
            'id' => $response->id,
            'number' => $response->number,
            'name' => $response->name,
            'timeout' => $response->timeout,
            'extensions' => $id ? $this->extensions($response, $token) : null
        ]);

        return $group;
    }

    protected function groups($response, AccessToken $token, $id = null) {
        if($id) {
            return $this->group($response->group, $token, $id);
        } else {
            $groups = array();
            $response_groups = $response->groups;
            foreach($response_groups as $response_group) {
                $group = $this->group($response_group, $token);

                array_push($groups, $group);

            }

            return $groups;
        }
    }

    public function getGroups(AccessToken $token) {
        $response = $this->fetchGroups($token);
        return $this->groups(json_decode($response), $token);
    }

    public function getGroup(AccessToken $token, $id) {
        $response = $this->fetchGroups($token, $id);
        return $this->groups(json_decode($response), $token, $id);
    }

    /* CDR METHODS */

    protected function urlCdr(AccessToken $token, $uuid = null, $page, $params = [])
    {
        $url = "";
        $params['access_token'] = $token->accessToken;
        $par = http_build_query($params);
        if($uuid) {
            $url = $this->baseUrl."/cdrs/". $uuid ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/cdrs?page=". $page ."&". $par;
        }
        return $url;
    }

    protected function fetchCdrs(AccessToken $token, $uuid = null, $page, $params = [])
    {
        $url = $this->urlCdr($token, $uuid, $page, $params);
        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function origination($response) {
        $originations = array();
        foreach($response as $o) {
            $origination = new Origination();
            $origination->exchangeArray([
                'destination' => $o->destination,
                'start_time' => $o->start_time,
                'answer_time' => $o->answer_time,
                'hangup_time' => $o->hangup_time,
                'result' => $o->result,
            ]);
            array_push($originations, $origination);
        }
        return $originations;
    }

    protected function callFlow($response)
    {
        $call_flows = array();
        foreach($response as $cf) {
            $call_flow = new CallFlow();
            $call_flow->exchangeArray([
                'callee' => $cf->callee,
                'start_time' => $cf->start_time,
                'answer_time' => $cf->answer_time,
                'hangup_time' => $cf->hangup_time,
                'redirection' => $cf->redirection,
                'redirection_target' => property_exists($cf, 'redirection_target') ? $cf->redirection_target : null,
                'origination' => property_exists($cf, 'origination') ? $this->origination($cf->origination) : null,
            ]);
            array_push($call_flows, $call_flow);
        }

        return $call_flows;
    }

    protected function cdr($response, $id = null) {
        $cdr = new Cdr();
        $cdr->exchangeArray([
            'uuid' => $response->uuid,
            'bf_calltype' => $response->bf_calltype,
            'direction' => $response->direction,
            'caller' => $response->caller,
            'callee' => $response->callee,
            'extension' => property_exists($response, 'extension') ? $response->extension : null,
            'call_price' => property_exists($response, 'call_price') ? $response->call_price : null,
            'call_time' => $response->call_time,
            'answer_time' => $response->answer_time,
            'hangup_time' => $response->hangup_time,
            'call_record' => $response->call_record,
            'hangup_cause' => $response->hangup_cause,
            'hangup_state' => $response->hangup_state,
            'call_flow' => property_exists($response, "call_flow") ? $this->callFlow($response->call_flow) : null,
        ]);
        return $cdr;
    }

    protected function cdrs($response, AccessToken $token, $uuid = null)
    {
        if($uuid) {
            return $this->cdr($response->cdr, $uuid);
        } else {
            $cdrs = array();
            $response_cdrs = $response->cdrs;
            foreach($response_cdrs as $response_cdr) {
                $cdr = $this->cdr($response_cdr, $uuid);

                array_push($cdrs, $cdr);

            }

            $pagination = $response->pagination;
            $cdrObj = new CdrObject();
            $cdrObj->exchangeArray([
                'cdrs' => $cdrs,
                'previous_page' => property_exists($pagination, "previous_page") ? $pagination->previous_page : null,
                'next_page' => property_exists($pagination, "next_page") ? $pagination->next_page : null,
                'page' => $pagination->page,
            ]);

            return $cdrObj;
        }
    }

    public function getCdrs(AccessToken $token, $params = [], $page = 1) {
        $response = $this->fetchCdrs($token, null, $page, $params);
        return $this->cdrs(json_decode($response), $token);
    }

    public function getCdr(AccessToken $token, $uuid) {
        $response = $this->fetchCdrs($token, $uuid, 1, []);
        return $this->cdrs(json_decode($response), $token, $uuid);
    }

    public function getUser(AccessToken $token) {
        return $this->getUserDetails($token);
    }

    /* CALL RECORD METHODS */

    protected function urlCallRecord(AccessToken $token, $id = null)
    {
        $url = $this->baseUrl."/call-records/". $id ."?access_token=".$token;
        return $url;
    }

    public function getCallRecord(AccessToken $token, $id, $path) {
        $url = $this->urlCallRecord($token, $id);
        $this->getFile($url, $path);
    }

    /* INCOMING FAX METHODS */

    protected function urlIncomingFax(AccessToken $token, $id = null)
    {
        $url = "";
        if($id) {
            $url = $this->baseUrl."/incoming-faxes/". $id ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/incoming-faxes?access_token=".$token;
        }
        return $url;
    }

    protected function fetchIncomingFaxes(AccessToken $token, $id = null)
    {
        $url = $this->urlIncomingFax($token, $id);
        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function incomingFax($response, AccessToken $token)
    {
        $incomingFax = new IncomingFax();
        $incomingFax->exchangeArray([
            'uuid' => $response->uuid,
            'sender' => $response->sender,
            'receiver' => $response->receiver,
            'created_at' => $response->created_at
        ]);

        return $incomingFax;
    }

    protected function incomingFaxes($response, AccessToken $token) {
        $incoming_faxes = array();
        $response_incoming_faxes = $response->incoming_faxes;
        foreach($response_incoming_faxes as $response_incoming_fax) {
            $incoming_fax = $this->incomingFax($response_incoming_fax, $token);

            array_push($incoming_faxes, $incoming_fax);

        }
        return $incoming_faxes;
    }

    public function getIncomingFaxes(AccessToken $token) {
        $response = $this->fetchIncomingFaxes($token);
        return $this->incomingFaxes(json_decode($response), $token);
    }

    public function getIncomingFax(AccessToken $token, $id, $path) {
        $url = $this->urlIncomingFax($token, $id);
        $this->getFile($url, $path);
    }

    /* OUTGOING FAX METHODS */

    protected function urlOutgoingFax(AccessToken $token, $id = null)
    {
        $url = "";
        if($id) {
            $url = $this->baseUrl."/outgoing-faxes/". $id ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/outgoing-faxes?access_token=".$token;
        }
        return $url;
    }

    protected function fetchOutgoingFaxes(AccessToken $token, $id = null)
    {
        $url = $this->urlOutgoingFax($token, $id);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function outGoingFaxRecipients($response, AccessToken $token) {
        $recipients = array();
        $response_recipients = $response->recipients;
        foreach($response_recipients as $response_recipient) {
            $recipient = new OutgoingFaxRecipient();
            $recipient->exchangeArray([
                'number' => $response_recipient->number,
                'state' => $response_recipient->state,
            ]);
            array_push($recipients, $recipient);
        }

        return $recipients;
    }

    protected function outGoingFax($response, AccessToken $token, $id = null)
    {
        $outgoingFax = new OutgoingFax();
        $outgoingFax->exchangeArray([
            'id' => $response->id,
            'title' => $response->title,
            'did' => $response->did,
            'recipient_count' => $response->recipient_count,
            'recipients' => $id ? $this->outGoingFaxRecipients($response, $token) : null,
            'created_at' => $response->created_at
        ]);

        return $outgoingFax;
    }

    protected function outGoingFaxes($response, AccessToken $token, $id = null) {
        if($id) {
            return $this->outGoingFax($response->fax, $token, $id);
        } else {
            $faxes = array();
            $response_faxes = $response->faxes;
            foreach($response_faxes as $response_fax) {
                $fax = $this->outGoingFax($response_fax, $token);

                array_push($faxes, $fax);

            }

            return $faxes;
        }
    }

    public function getOutgoingFaxes(AccessToken $token) {
        $response = $this->fetchOutgoingFaxes($token);
        return $this->outGoingFaxes(json_decode($response), $token);
    }

    public function getOutgoingFax(AccessToken $token, $id) {
        $response = $this->fetchOutgoingFaxes($token, $id);
        return $this->outGoingFaxes(json_decode($response), $token, $id);
    }

    public function prepareFaxAttachment($path) {
        $type = mime_content_type($path);
        $basename = basename($path, pathinfo($path, PATHINFO_EXTENSION));
        $data = file_get_contents($path);
        $base64 = 'data:'. $type . ';name:'. $basename .';base64:' . base64_encode($data);
        return $base64;
    }

    public function sendFax(AccessToken $token, $params) {
        $url = $this->urlOutgoingFax($token);
        $f_path = $params['attachment'];
        $params['attachment'] = $this->prepareFaxAttachment($f_path);
        $response = $this->postProviderData($url, $params);
        return json_decode($response);
    }

    /* ANNOUNCEMENT METHODS */


    protected function urlAnnouncement(AccessToken $token, $id = null)
    {
        $url = "";
        if($id) {
            $url = $this->baseUrl."/announcements/". $id ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/announcements?access_token=".$token;
        }
        return $url;
    }

    protected function fetchAnnouncements(AccessToken $token, $id = null)
    {
        $url = $this->urlAnnouncement($token, $id);
        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function announcement($response, AccessToken $token)
    {
        $announcement = new Announcement();
        $announcement->exchangeArray([
            'id' => $response->id,
            'name' => $response->name,
            'file_name' => $response->file_name,
            'is_on_hold_music' => $response->is_on_hold_music,
            'created_at' => $response->created_at
        ]);

        return $announcement;
    }

    protected function announcements($response, AccessToken $token) {
        $announcements = array();
        $response_announcements = $response->announcements;
        foreach($response_announcements as $response_announcement) {
            $announcement = $this->announcement($response_announcement, $token);

            array_push($announcements, $announcement);

        }
        return $announcements;
    }

    public function getAnnouncements(AccessToken $token) {
        $response = $this->fetchAnnouncements($token);
        return $this->announcements(json_decode($response), $token);
    }

    public function getAnnouncement(AccessToken $token, $id, $path) {
        $url = $this->urlAnnouncement($token, $id);
        $this->getFile($url, $path);
    }

    /* AUTOMATIC CALL METHODS */

    protected function urlAutomaticCall(AccessToken $token, $id = null)
    {
        $url = "";
        if($id) {
            $url = $this->baseUrl."/automatic-calls/". $id ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/automatic-calls?access_token=".$token;
        }
        return $url;
    }

    protected function fetchAutomaticCalls(AccessToken $token, $id = null)
    {
        $url = $this->urlAutomaticCall($token, $id);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function automaticCallRecipients($response, AccessToken $token) {
        $recipients = array();
        $response_recipients = $response->recipients;
        foreach($response_recipients as $response_recipient) {
            $recipient = new AutomaticCallRecipient();
            $recipient->exchangeArray([
                'number' => $response_recipient->number,
                'has_called' => $response_recipient->has_called,
                'gather' => $response_recipient->gather,
            ]);
            array_push($recipients, $recipient);
        }

        return $recipients;
    }

    protected function automaticCall($response, AccessToken $token, $id = null)
    {
        $automaticCall = new AutomaticCall();
        $automaticCall->exchangeArray([
            'id' => $response->id,
            'title' => $response->title,
            'did' => $response->did,
            'announcement' => $response->announcement,
            'gather' => $response->gather,
            'recipients' => $id ? $this->automaticCallRecipients($response, $token) : null,
            'call_range' => ($id) ? $this->workingHours($response->call_range) : null,
            'created_at' => $response->created_at
        ]);

        return $automaticCall;
    }

    protected function automaticCalls($response, AccessToken $token, $id = null) {
        if($id) {
            return $this->automaticCall($response->automatic_call, $token, $id);
        } else {
            $automatic_calls = array();
            $response_calls = $response->automatic_calls;
            foreach($response_calls as $response_call) {
                $call = $this->automaticCall($response_call, $token);

                array_push($automatic_calls, $call);

            }

            return $automatic_calls;
        }
    }

    public function getAutomaticCalls(AccessToken $token) {
        $response = $this->fetchAutomaticCalls($token);
        return $this->automaticCalls(json_decode($response), $token);
    }

    public function getAutomaticCall(AccessToken $token, $id) {
        $response = $this->fetchAutomaticCalls($token, $id);
        return $this->automaticCalls(json_decode($response), $token, $id);
    }

    public function createAutomaticCall(AccessToken $token, $params) {
        $url = $this->urlAutomaticCall($token);
        $response = $this->postProviderData($url, $params);
        return json_decode($response);
    }

    /* Message TITLE METHODS */


    protected function urlMessageTitle(AccessToken $token)
    {
        $url = $this->baseUrl."/message-titles?access_token=".$token;
        return $url;
    }

    protected function fetchMessageTitles(AccessToken $token)
    {
        $url = $this->urlMessageTitle($token);
        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function messageTitle($response, AccessToken $token)
    {
        $messageTitle = new MessageTitle();
        $messageTitle->exchangeArray([
            'id' => $response->id,
            'name' => $response->name,
            'state' => $response->state
        ]);

        return $messageTitle;
    }

    protected function messageTitles($response, AccessToken $token) {
        $messageTitles = array();
        $response_messageTitles = $response->message_titles;
        foreach($response_messageTitles as $response_messageTitle) {
            $messageTitle = $this->messageTitle($response_messageTitle, $token);

            array_push($messageTitles, $messageTitle);

        }
        return $messageTitles;
    }

    public function getMessageTitles(AccessToken $token) {
        $response = $this->fetchMessageTitles($token);
        return $this->messageTitles(json_decode($response), $token);
    }

    /* MESSAGE METHODS */

    protected function urlMessage(AccessToken $token, $id = null)
    {
        $url = "";
        if($id) {
            $url = $this->baseUrl."/messages/". $id ."?access_token=".$token;
        } else {
            $url = $this->baseUrl."/messages?access_token=".$token;
        }
        return $url;
    }

    protected function fetchMessages(AccessToken $token, $id = null)
    {
        $url = $this->urlMessage($token, $id);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function messageRecipient($response, AccessToken $token) {
        $recipients = array();
        $response_recipients = $response->recipients;
        foreach($response_recipients as $response_recipient) {
            $recipient = new MessageRecipient();
            $recipient->exchangeArray([
                'number' => $response_recipient->number,
                'state' => $response_recipient->state,
            ]);
            array_push($recipients, $recipient);
        }

        return $recipients;
    }

    protected function message($response, AccessToken $token, $id = null)
    {
        $message = new Message();
        $message->exchangeArray([
            'id' => $response->id,
            'title' => $response->title,
            'content' => $response->content,
            'sent_as_single_sms' => $response->sent_as_single_sms,
            'is_planned_sms' => $id ?  $response->is_planned_sms : null,
            'send_date' => $id ?  $response->send_date : null,
            'recipients' => $id ?  $this->messageRecipient($response, $token) : null,
            'created_at' => $response->created_at
        ]);

        return $message;
    }

    protected function messages($response, AccessToken $token, $id = null) {
        if($id) {
            return $this->message($response->message, $token, $id);
        } else {
            $messages = array();
            $response_messages = $response->messages;
            foreach($response_messages as $response_message) {
                $message = $this->message($response_message, $token);

                array_push($messages, $message);

            }

            return $messages;
        }
    }

    public function getMessages(AccessToken $token) {
        $response = $this->fetchMessages($token);
        return $this->messages(json_decode($response), $token);
    }

    public function getMessage(AccessToken $token, $id) {
        $response = $this->fetchMessages($token, $id);
        return $this->messages(json_decode($response), $token, $id);
    }

    public function sendMessage(AccessToken $token, $params) {
        $url = $this->urlMessage($token);
        $response = $this->postProviderData($url, $params);
        return json_decode($response);
    }

    /* API INFO METHODS */

    protected function tokenInfoUrl(AccessToken $token)
    {
        $url = "";
        $url = $this->baseUrl."/token-info/".$token;
        return $url;
    }

    protected function fetchTokenInfo(AccessToken $token)
    {
        $url = $this->tokenInfoUrl($token);

        $headers = $this->getHeaders($token);

        return $this->fetchProviderData($url, $headers);
    }

    protected function tokenInfo($response, AccessToken $token)
    {
        $token_info = new TokenInfo();
        $token_info->exchangeArray([
            'token' => $response->token,
            'expired' => property_exists($response, "expired") ? $response->expired : null,
            'expires_in' => property_exists($response, "expires_in") ? $response->expires_in : null,
        ]);

        return $token_info;
    }

    public function getTokenInfo(AccessToken $token) {
        $response = $this->fetchTokenInfo($token);
        return $this->tokenInfo(json_decode($response), $token);
    }
}
