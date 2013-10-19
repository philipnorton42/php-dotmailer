<?php

namespace DotMailer;

/**
 * @mainpage
 * dotMailer API integration class.
 *
 * @author Philip Norton <philipnorton42@gmail.com>
 *
 * @see http://www.dotmailer.co.uk/
 * @see https://github.com/philipnorton42/PHP-Dotmailer
 */

/**
 * dotMailer API integration class.
 *
 * @author Philip Norton <philipnorton42@gmail.com>
 */
class DotMailer
{

    /**
     * The URL of the API.
     *
     * @var string
     */
    private $request_url = 'http://apiconnector.com/api.asmx?WSDL';
    
    /**
     * The username used in the API connection.
     *
     * @var string
     */
    private $username;
    
    /**
     * The password used in the API connection.
     *
     * @var string
     */
    private $password;
    
    /**
     * The SoapClient object.
     *
     * @var resource 
     */
    private $client;
    
    /**
     * Contains any errors generated during the last SoapClient call.
     *
     * @var mixed
     */
    private $lastFault = false;

    /**
     * Set up the SOAP client.
     *
     * @param string $username The account username.
     * @param string $password The account password.
     *
     * @throws UsernameAndPasswordNotFoundException
     */
    public function __construct($username, $password)
    {
        if ($username == '' && $password == '') {
            throw new UsernameAndPasswordNotFoundException();
        }

        $this->username = $username;
        $this->password = $password;
        $this->client = new SoapClient($this->request_url);
    }

    /**
     * Get the last SOAP fault.
     *
     * @return mixed False if no fault found, otherwise returns SoapFault object.
     */
    public function getLastFault()
    {
        return $this->lastFault;
    }

    /**
     * Given a method and an array of parameters make a SOAP call using the client.
     *
     * Returns false if an error occurred whilst processing the request. If an
     * error did occurr then the $lastFault parameter is filled in with the
     * error response from dotMailer.
     *
     * @param string $method The SOAP method to be called.
     * @param array $parameters An array of paramters.
     *
     * @return mixed The result of the SOAP call.
     */
    protected function makeSoapCall($method, $parameters)
    {
        $this->lastFault = false;
        try {
            return $this->client->$method($parameters);
        } catch (SoapFault $fault) {
            $this->lastFault = $fault;
            return false;
        }
    }

    /**
     * List all of the address books.
     *
     * @return array A list of address books available.
     */
    public function ListAddressBooks()
    {
        $parameters = array(
            'username' => $this->username,
            'password' => $this->password
        );

        $result = $this->makeSoapCall('ListAddressBooks', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->ListAddressBooksResult->APIAddressBook;
    }

    /**
     * Get the contacts from an address book.
     *
     * @param int  $addressBookId The Address book ID.
     * @param int  $select        How many records to select.
     * @param int $skip          How many records to skip.
     *
     * @return type An array of contacts.
     *
     * @throws MissingRequiredParametersException
     */
    public function ListContactsInAddressBook($addressBookId, $select, $skip)
    {

        if ($addressBookId == 0) {
            throw new MissingRequiredParametersException('$addressBookId is required.');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'addressBookId' => $addressBookId,
            'select' => $select,
            'skip' => $skip
        );
        $result = $this->client->ListContactsInAddressBook($parameters);

        $result = $this->makeSoapCall('ListContactsInAddressBook', $parameters);
        if ($result === false || !isset($result->ListContactsInAddressBookResult->APIContact)) {
            return array();
        }

        return $result->ListContactsInAddressBookResult->APIContact;
    }

    /**
     * Validate a contact array
     *
     * @param type $contact
     * @return type
     * @throws InvalidParametersException
     */
    protected function validateContactArray($contact)
    {
        if (!isset($contact['AudienceType'])) {
            $contact['AudienceType'] = 'B2C';
        } elseif (!in_array($contact['AudienceType'], array('Unknown', 'B2C', 'B2B', 'B2M'))) {
            throw new InvalidParametersException('AudienceType must be one of Unknown, B2C, B2B or B2M');
        }

        if (!isset($contact['OptInType'])) {
            $contact['OptInType'] = 'Single';
        } elseif (!in_array($contact['OptInType'], array('Unknown', 'Single', 'Double', 'VerifiedDouble'))) {
            throw new InvalidParametersException('OptInType must be one of Unknown, Single, Double or VerifiedDouble');
        }

        if (!isset($contact['EmailType'])) {
            $contact['EmailType'] = 'Html';
        } elseif (!in_array($contact['EmailType'], array('PlainText', 'Html'))) {
            throw new InvalidParametersException('EmailType must be one of PlainText or Html');
        }

        if (!isset($contact['ID'])) {
            $contact['ID'] = -1;
        } elseif (!is_numeric($contact['ID'])) {
            throw new InvalidParametersException('ID must be a number.');
        }

        return $contact;
    }

    /**
     * Validate a contact object
     *
     * @param type $contact
     * @return type
     * @throws InvalidParametersException
     */
    protected function validateContactObject($contact)
    {
        if (!isset($contact->AudienceType)) {
            $contact->AudienceType = 'B2C';
        } elseif (!in_array($contact->AudienceType, array('Unknown', 'B2C', 'B2B', 'B2M'))) {
            throw new InvalidParametersException('AudienceType must be one of Unknown, B2C, B2B or B2M');
        }

        if (!isset($contact->OptInType)) {
            $contact->OptInType = 'Single';
        } elseif (!in_array($contact->OptInType, array('Unknown', 'Single', 'Double', 'VerifiedDouble'))) {
            throw new InvalidParametersException('OptInType must be one of Unknown, Single, Double or VerifiedDouble');
        }

        if (!isset($contact->EmailType)) {
            $contact->EmailType = 'Html';
        } elseif (!in_array($contact->EmailType, array('PlainText', 'Html'))) {
            throw new InvalidParametersException('EmailType must be one of PlainText or Html');
        }

        if (!isset($contact->ID)) {
            $contact->ID = -1;
        } elseif (!is_numeric($contact->ID)) {
            throw new InvalidParametersException('ID must be a number.');
        }

        return $contact;
    }

    /**
     * Helper function, will validate a contact object or array, depending on the type.
     *
     * @param mixed $contact Either a contact object or a contact array.
     *
     * @return mixed The validated contact object.
     * @throws Exception
     */
    public function validateContact($contact)
    {
        if (is_array($contact)) {
            return $this->validateContactArray($contact);
        } elseif (is_object($contact)) {
            return $this->validateContactObject($contact);
        }
        throw new Exception('Invalid contact type found.');
    }

    /**
     * Add a contact to an address book.
     *
     * @param array $contact       The basic contact fields.
     * @param array $fields        Any additional data fields.
     * @param int   $addressBookId The ID of the address book.
     *
     * @return object The returned contact object.
     *
     * @throws MissingRequiredParametersException
     */
    public function AddContactToAddressBook($contact, $fields, $addressBookId)
    {

        if ($addressBookId == 0 || !is_numeric($addressBookId)) {
            throw new MissingRequiredParametersException('$addressBookId is required.');
        }

        $contact = $this->validateContact($contact);

        $keys = array();
        $values = array();

        foreach ($fields as $key => $item) {
            if (is_array($item)) {
                $values[] = new SoapVar(
                                $item['data'],
                                $this->typeConversion($item['type']),
                                $item['type'],
                                "http://www.w3.org/2001/XMLSchema"
                );
            } else {
                $values[] = new SoapVar(
                                $item,
                                XSD_STRING,
                                "string",
                                "http://www.w3.org/2001/XMLSchema"
                );
            }
            $keys[] = $key;
        }

        $fields = array(
            'Keys' => $keys,
            'Values' => $values
        );

        $contact['DataFields'] = $fields;

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'contact' => $contact,
            'addressbookId' => $addressBookId
        );

        $result = $this->makeSoapCall('AddContactToAddressBook', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->AddContactToAddressBookResult;
    }

    /**
     * Convert a type into a XSD constant.
     *
     * @param string $type The type to be converted.
     *
     * @return int The XSD constant for that type.
     */
    public function typeConversion($type)
    {
        switch ($type) {
            case 'string':
                return XSD_STRING;
            case 'int':
                return XSD_INT;
        }
    }

    /**
     * Removes all contacts from an address book.
     *
     * @param int $addressBookId The ID of the address book.
     * @param boolean $preventAddressbookResubscribe Flag to prevent the contact
     *                                               resubscribing to this list,
     *                                               defaults to false.
     * @param boolean $totalUnsubscribe Completely unsubscribe this contact,
     *                                  defaults to false.
     *
     * @return object
     *
     * @throws MissingRequiredParametersException
     */
    public function RemoveAllContactsFromAddressBook($addressBookId, $preventAddressbookResubscribe = false, $totalUnsubscribe = false)
    {
        if ($addressBookId == 0) {
            throw new MissingRequiredParametersException('$addressBookId is required.');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'addressBookId' => $addressBookId,
            'preventAddressbookResubscribe' => $preventAddressbookResubscribe,
            'totalUnsubscribe' => $totalUnsubscribe
        );

        $result = $this->makeSoapCall('RemoveAllContactsFromAddressBook', $parameters);

        if ($result === false || !isset($result->RemoveAllContactsFromAddressBook->APIContact)) {
            return false;
        }

        return $result;
    }

    /**
     * Add a collection of contacts to an address book. The progress ID is returned
     * which can be used to find out if the current import process is complete.
     *
     * @param int    $addressBookId The ID of the address book.
     * @param string $data The actual data, as extracted from a file.
     * @param string $dataType The type of data, currently only CSV and XLS are allowed.
     *
     * @return string The GUID of the process.
     *
     * @throws InvalidFileFormatException
     */
    public function AddContactsToAddressBookWithProgress($addressBookId, $data, $dataType)
    {

        switch (strtoupper($dataType)) {
            case 'CSV':
            case 'XLS':
                break;
            default:
                throw new InvalidFileFormatException('Data type is unknown.');
        }

        $encodedData = base64_encode($data);

        $typedVar = new SoapVar($encodedData, XSD_BASE64BINARY, "string", "http://www.w3.org/2001/XMLSchema");

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'addressbookID' => $addressBookId,
            'data' => $typedVar,
            'dataType' => $dataType
        );

        $result = $this->makeSoapCall('AddContactsToAddressBookWithProgress', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->AddContactsToAddressBookWithProgressResult;
    }

    /**
     * Find out the progress of the import process is going.
     *
     * @param string $progressID The GUID of the process
     *
     * @return string One of Finished, NotFinished, RejectedByWatchdog,
     *                InvalidFileFormat or Unknown
     */
    public function GetContactImportProgress($progressID)
    {

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'progressID' => $progressID,
        );

        $result = $this->makeSoapCall('GetContactImportProgress', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->GetContactImportProgressResult;
    }

    /**
     * Get the number of contacts in an address book.
     *
     * @param int $addressBookId The ID of the address book.
     *
     * @return int The number of contacts in the address book.
     */
    public function GetAddressBookContactCount($addressBookId)
    {
        if ($addressBookId == 0 || !is_numeric($addressBookId)) {
            throw new MissingRequiredParametersException('$addressBookId is required.');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'addressbookid' => $addressBookId,
        );

        $result = $this->makeSoapCall('GetAddressBookContactCount', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->GetAddressBookContactCountResult;
    }

    /**
     * Create an address book
     *
     * @param string $name The name of the address book to create.
     *
     * @return object The newly created address book object.
     */
    public function CreateAddressBook($name)
    {
        $book = new stdClass();
        $book->Name = $name;
        $book->ID = -1;

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'book' => $book
        );

        $result = $this->makeSoapCall('CreateAddressBook', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->CreateAddressBookResult;
    }

    /**
     * Delete an address book.
     *
     * @param int $addressBookId The ID of the address book.
     *
     * @return boolean True if no problems encountered.
     *
     * @throws MissingRequiredParametersException
     * @throws Exception
     */
    public function DeleteAddressBook($addressBookId)
    {
        if ($addressBookId == 0 || !is_numeric($addressBookId)) {
            throw new MissingRequiredParametersException('$addressBookId is required.');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'addressbookid' => $addressBookId,
        );

        $result = $this->makeSoapCall('DeleteAddressBook', $parameters);

        if ($result === false) {
            return false;
        }

        return true;
    }

    /**
     * Get a contact via their email address.
     *
     * @param string $email The contact's email address.
     *
     * @return object The contact.
     */
    public function GetContactByEmail($email)
    {
        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'email' => $email
        );

        $result = $this->makeSoapCall('GetContactByEmail', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->GetContactByEmailResult;
    }

    /**
     * Get a contact via their dotmailer ID.
     *
     * @param int $id The contact's dotmailer ID
     *
     * @return object The contact.
     */
    public function GetContactById($id)
    {
        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'id' => $id
        );

        $result = $this->makeSoapCall('GetContactById', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->GetContactByIdResult;
    }

    /**
     * Create a contact.
     *
     * @param type $contact
     * @param type $fields
     */
    public function CreateContact($contact, $fields = null)
    {
        $contact = $this->validateContact($contact);

        if ($fields) {
            $keys = array();
            $values = array();

            foreach ($fields as $key => $item) {
                if (is_array($item)) {
                    $values[] = new SoapVar($item['data'], $this->typeConversion($item['type']), $item['type'], "http://www.w3.org/2001/XMLSchema");
                } else {
                    $values[] = new SoapVar($item, XSD_STRING, "string", "http://www.w3.org/2001/XMLSchema");
                }
                $keys[] = $key;
            }

            $fields = array(
                'Keys' => $keys,
                'Values' => $values
            );

            $contact['DataFields'] = $fields;
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'contact' => $contact
        );

        $result = $this->makeSoapCall('CreateContact', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->CreateContactResult;
    }

    /**
     * Update an existing contact. Either the email address or the ID are required.
     *
     * @param array $contact The contact to update
     * @param array $fields Any fields to be included in with the contact.
     *
     * @return object The result object, in this case it is the contact.
     */
    public function UpdateContact($contact, $fields = null)
    {
        $contact = $this->validateContact($contact);

        if ($fields) {
            $keys = array();
            $values = array();

            foreach ($fields as $key => $item) {
                if (is_array($item)) {
                    $values[] = new SoapVar($item['data'], $this->typeConversion($item['type']), $item['type'], "http://www.w3.org/2001/XMLSchema");
                } else {
                    $values[] = new SoapVar($item, XSD_STRING, "string", "http://www.w3.org/2001/XMLSchema");
                }
                $keys[] = $key;
            }

            $fields = array(
                'Keys' => $keys,
                'Values' => $values
            );

            $contact['DataFields'] = $fields;
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'contact' => $contact
        );

        $result = $this->makeSoapCall('UpdateContact', $parameters);

        if ($result === false) {
            return false;
        }

        return true;
    }

    /**
     * Get a list of the available data labels.
     *
     * @return object An array of availble ContactDataLabel objects.
     */
    public function ListContactDataLabels()
    {
        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
        );

        $result = $this->makeSoapCall('ListContactDataLabels', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->ListContactDataLabelsResult;
    }

    /**
     * Get information about the current account.
     *
     * @return object An array of APIAccountProperty objects.
     */
    public function GetCurrentAccountInfo()
    {
        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
        );

        $result = $this->makeSoapCall('GetCurrentAccountInfo', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->GetCurrentAccountInfoResult;
    }

    /**
     * Helper function to correct the fields returned when finding contacts.
     *
     * @param object $contact The contact object.
     */
    public function flattenContactFields($contact)
    {
        $contact->fields = array();
        foreach ($contact->DataFields->Keys->string as $id => $dataField) {
            $contact->fields[$dataField] = $contact->DataFields->Values->anyType[$id];
        }
        return $contact;
    }

    /**
     * Get the current server time from dotmailer.
     *
     * @return string The time
     */
    public function GetServerTime()
    {

        $result = $this->makeSoapCall('GetServerTime', array());

        if ($result === false) {
            return false;
        }

        return $result->GetServerTimeResult;
    }

    /**
     * SOAP Debug wrapper to get the last request body content.
     *
     * @return string The last request body content.
     */
    public function getLastRequest()
    {
        return $this->client->__getLastRequest();
    }

    /**
     * SOAP Debug wrapper to get the last response body content.
     * @return type
     */
    public function getLastResponse()
    {
        return $this->client->__getLastResponse();
    }

    /**
     * Given a start date return the campaigns that have been send since then. The
     * date must be in the format [-]CCYY-MM-DDThh:mm:ss[Z|(+|-)hh:mm] See
     * http://books.xmlschemata.org/relaxng/ch19-77049.html for more information
     * about the date format.
     *
     * @param string $startDate The start date of the campaign.
     *
     * @return array A set of campaign objects.
     *
     * @throws InvalidDateTimeFormatException
     */
    public function ListSentCampaignsWithActivitySinceDate($startDate)
    {
        $date_regex = '/(\-)?\d{4}-[0-1][0-9]-[0-3][0-9]T[0-2][0-9]:[0-5][0-9]:[0-5][0-9](Z|(\+|\-)[0-5][0-9]:[0-5][0-9])?/';
        if (preg_match($date_regex, $startDate) == 0) {
            throw new InvalidDateTimeFormatException('startDate is invalid.');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'startDate' => $startDate
        );

        $result = $this->makeSoapCall('ListSentCampaignsWithActivitySinceDate', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->ListSentCampaignsWithActivitySinceDateResult->APICampaign;
    }

    /**
     * Get specific information about a campaign from a given ID.
     *
     * @param int $campaignId The campaign ID.
     *
     * @return The Campaign object, or false if an exception occurred.
     *
     * @throws InvalidParametersException
     * @throws Exception
     */
    public function GetCampaign($campaignId)
    {
        if (!is_numeric($campaignId)) {
            throw new InvalidParametersException('Invalid Campaign ID');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'campaignId' => $campaignId
        );

        $result = $this->makeSoapCall('GetCampaign', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->GetCampaignResult;
    }

    /**
     * Return a summary for a campaign.
     *
     * @param int $campaignId The campaign ID
     *
     * @return object An object containing a summary of the campaign.
     *
     * @throws InvalidParametersException
     */
    public function GetCampaignSummary($campaignId)
    {
        if (!is_numeric($campaignId)) {
            throw new InvalidParametersException('Invalid Campaign ID');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'campaignId' => $campaignId
        );

        $result = $this->makeSoapCall('GetCampaignSummary', $parameters);

        if ($result === false) {
            return false;
        }

        return $result->GetCampaignSummaryResult;
    }

    /**
     * List the address books assiciated with a campaign.
     *
     * Returns false if anything went wrong.
     *
     * @param int $campaignId The campaign
     * @return mixed The result object.
     *
     * @throws InvalidParametersException
     */
    public function ListAddressBooksForCampaign($campaignId)
    {
        if (!is_numeric($campaignId)) {
            throw new InvalidParametersException('Invalid Campaign ID');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'campaignID' => $campaignId
        );

        $result = $this->makeSoapCall('ListAddressBooksForCampaign', $parameters);

        if ($result === false || !isset($result->ListAddressBooksForCampaignResult->APIAddressBook)) {
            return false;
        }

        return $result->ListAddressBooksForCampaignResult->APIAddressBook;
    }

    /**
     * Lists all the address books that a particular contact exists in.
     *
     * @param mixes $contact The contact as either an object or an array.
     * @return mixed False if no address books found, otherwise returns the result object.
     */
    public function ListAddressBooksForContact($contact)
    {
        $contact = $this->validateContact($contact);

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'contact' => $contact
        );

        $result = $this->makeSoapCall('ListAddressBooksForContact', $parameters);

        if ($result === false || !isset($result->ListAddressBooksForContactResult->APIAddressBook)) {
            return false;
        }
        return $result->ListAddressBooksForContactResult->APIAddressBook;
    }

    /**
     * Send a campaign to a sngle email address.
     *
     * @param int $campaignId The campaign ID.
     * @param int $contactId The contact ID.
     * @param string $sendDate The date and time to send the campaign.
     *
     * @return boolean False if anything went wrong, otherwise true.
     *
     * @throws InvalidParametersException
     * @throws InvalidDateTimeFormatException
     */
    public function SendCampaignToContact($campaignId, $contactId, $sendDate)
    {
        if (!is_numeric($campaignId)) {
            throw new InvalidParametersException('Invalid campaign ID given.');
        }

        if (!is_numeric($contactId)) {
            throw new InvalidParametersException('Invalid contact ID given.');
        }

        $date_regex = '/(\-)?\d{4}-[0-1][0-9]-[0-3][0-9]T[0-2][0-9]:[0-5][0-9]:[0-5][0-9](Z|(\+|\-)[0-5][0-9]:[0-5][0-9])?/';
        if (preg_match($date_regex, $sendDate) == 0) {
            throw new InvalidDateTimeFormatException('startDate is invalid.');
        }

        $parameters = array(
            'username' => $this->username,
            'password' => $this->password,
            'campaignId' => $campaignId,
            'contactid' => $contactId,
            'sendDate' => $sendDate
        );

        $result = $this->makeSoapCall('SendCampaignToContact', $parameters);

        if ($result === false) {
            return false;
        }

        return true;
    }

    /**
     * Was the last request an error?
     *
     * @return boolean True if no errors were found, false if there were.
     */
    public function isError()
    {
        if ($this->lastFault === false) {
            return false;
        }

        return true;
    }

}

class UsernameAndPasswordNotFoundException extends \Exception
{
    
}

class MissingRequiredParametersException extends \Exception
{
    
}

class AddressBookNotFoundException extends \Exception
{
    
}

class InvalidParametersException extends \Exception
{
    
}

class InvalidDateTimeFormatException Extends \InvalidParametersException
{
    
}

class InvalidFileFormatException extends \Exception
{
    
}
