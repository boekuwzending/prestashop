<?php

namespace Boekuwzending\PrestaShop\Service;

use Boekuwzending\Client;
use Boekuwzending\ClientFactory;
use Boekuwzending\Exception\AuthorizationFailedException;
use Boekuwzending\Exception\RequestFailedException;
use Boekuwzending\PrestaShop\Utils\AddressParser;
use Boekuwzending\Resource\Address;
use Boekuwzending\Resource\Contact;
use Boekuwzending\Resource\Order;
use Boekuwzending\Resource\OrderLine;

class BoekuwzendingClient
{
    /**
     * @var Client
     */
    private $client;
    /**
     * @var AddressParser
     */
    private $addressParser;
    /**
     * @var string
     */
    private $environment;

    public function __construct(AddressParser $addressParser)
    {
        $this->addressParser = $addressParser;
        $liveMode = \Configuration::get('BOEKUWZENDING_LIVE_MODE');
        $this->environment = filter_var($liveMode, FILTER_VALIDATE_BOOLEAN) === true ? Client::ENVIRONMENT_LIVE : Client::ENVIRONMENT_STAGING;
        $clientId = \Configuration::get('BOEKUWZENDING_CLIENT_ID');
        $clientSecret = \Configuration::get('BOEKUWZENDING_CLIENT_SECRET');

        $clientFactory = new ClientFactory();
        $this->client = $clientFactory->build($clientId, $clientSecret, $this->environment);
    }

    public function getBoekuwzendingOrderUrl(): string
    {
        if ($this->environment === Client::ENVIRONMENT_STAGING) {
            return "https://staging.mijn.boekuwzending.com/bestellingen/{id}/bewerken";
        }
        return "https://mijn.boekuwzending.com/bestellingen/{id}/bewerken";
    }

    /**
     * @throws AuthorizationFailedException
     * @throws RequestFailedException
     */
    public function createOrder(\Order $order): Order
    {
        $buzOrder = $this->mapOrder($order);
        return $this->client->order->create($buzOrder);
    }

    private function mapOrder(\Order $order): Order
    {
        $buzOrder = new Order("0");

        // Bookkeeping fields
        $buzOrder->setExternalId($order->id);
        $buzOrder->setReference($order->reference);
        $buzOrder->setCreatedAtSource(new \DateTime($order->date_add));

        // Re-read these objects from the database, they're not Order properties
        $customer = new \Customer($order->id_customer);
        $shippingAddress = new \Address($order->id_address_delivery);

        // Contact
        $contact = new Contact();
        $contact->setName($customer->firstname . " " . $customer->lastname);
        $contact->setCompany($customer->company);

        // This appears to be a legacy field, first give it a try...
        $phone = $shippingAddress->phone_mobile;
        if (!$phone)
        {
            // But fall back to the regular phone field.
            $phone = $shippingAddress->phone;
        }
        $contact->setPhoneNumber($phone);
        $contact->setEmailAddress($customer->email);

        $buzOrder->setShipToContact($contact);

        // Address
        $street = $shippingAddress->address1 . " " . $shippingAddress->address2;
        $parsedAddress = $this->addressParser->parseAddressLine($street);

        $address = new Address();

        $address->setStreet($parsedAddress->street);
        $address->setNumber($parsedAddress->number);
        $address->setNumberAddition($parsedAddress->numberAddition);

        $address->setPostcode($shippingAddress->postcode);
        $address->setCity($shippingAddress->city);

        $country = new \Country($shippingAddress->id_country);
        $address->setCountryCode($country->iso_code);

        $buzOrder->setShipToAddress($address);

        // Order items
        $lines = [];
        foreach ($order->getProducts() as $detailId => $item) {

            $line = new OrderLine();

            $line->setExternalId($detailId); // or "id_order_detail", or "unique_id?"
            $line->setDescription($item["product_name"]);
            $line->setQuantity((int)$item["product_quantity"]);
            $line->setValue($item["product_price_wt"]); // or "total_wt", is price * quantity?

            if ($item["product_weight"]) {
                $line->setWeight($item["product_weight"]);
            }

            // Dimensions not yet implemented by the SDK.
            //if ($item["width"] && $item["height"] && $item["depth"]) {
            //    $dimensions = new Dimensions();
            //    $dimensions->setWidth((int)$item["width"]);
            //    $dimensions->setHeight((int)$item["height"]);
            //    $dimensions->setLength((int)$item["depth"]);
            //
            //    $line->setDimensions($dimensions);
            //}

            $lines[] = $line;
        }

        $buzOrder->setOrderLines($lines);

        return $buzOrder;
    }
}