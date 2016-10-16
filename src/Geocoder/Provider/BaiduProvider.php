<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider;

use Geocoder\Exception\InvalidCredentials;
use Geocoder\Exception\NoResult;
use Geocoder\Exception\UnsupportedOperation;
use Ivory\HttpAdapter\HttpAdapterInterface;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class BaiduProvider extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    const GEOCODE_ENDPOINT_URL = 'http://api.map.baidu.com/geocoder/v2/?output=json&pois=0&ak=%s&address=%s';

    /**
     * @var string
     */
    const REVERSE_ENDPOINT_URL = 'http://api.map.baidu.com/geocoder/v2/?output=json&pois=0&ak=%s&location=%F,%F';

    /**
     * @var string
     */
    private $apiKey = null;

    /**
     * @param HttpAdapterInterface $adapter An HTTP adapter.
     * @param string               $apiKey  An API key.
     */
    public function __construct(HttpAdapterInterface $adapter, $apiKey)
    {
        parent::__construct($adapter);

        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function geocode($address)
    {
        if (null === $this->apiKey) {
            throw new InvalidCredentials('No API Key provided');
        }

        // This API doesn't handle IPs
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The Baidu provider does not support IP addresses.');
        }

        $query = sprintf(self::GEOCODE_ENDPOINT_URL, $this->apiKey, rawurlencode($address));

        return $this->executeQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function reverse($latitude, $longitude)
    {
        if (null === $this->apiKey) {
            throw new InvalidCredentials('No API Key provided');
        }

        $query = sprintf(self::REVERSE_ENDPOINT_URL, $this->apiKey, $latitude, $longitude);

        return $this->executeQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'baidu';
    }

    /**
     * @param string $query
     *
     * @return array
     */
    protected function executeQuery($query)
    {
        $content = (string) $this->getAdapter()->get($query)->getBody();

        if (null === $content) {
            throw new NoResult(sprintf('Could not execute query %s', $query));
        }

        $data = (array) json_decode($content, true);

        if (empty($data) || false === $data) {
            throw new NoResult(sprintf('Could not execute query %s', $query));
        }

        if ('INVALID_KEY' === $data['status']) {
            throw new InvalidCredentials('API Key provided is not valid.');
        }

        return array(array_merge($this->getDefaults(), array(
            'latitude'     => isset($data['result']['location']['lat']) ? $data['result']['location']['lat'] : null,
            'longitude'    => isset($data['result']['location']['lng']) ? $data['result']['location']['lng'] : null,
            'streetNumber' => isset($data['result']['addressComponent']['street_number']) ? $data['result']['addressComponent']['street_number'] : null,
            'streetName'   => isset($data['result']['addressComponent']['street']) ? $data['result']['addressComponent']['street'] : null,
            'city'         => isset($data['result']['addressComponent']['city']) ? $data['result']['addressComponent']['city'] : null,
            'cityDistrict' => isset($data['result']['addressComponent']['district']) ? $data['result']['addressComponent']['district'] : null,
            'county'       => isset($data['result']['addressComponent']['province']) ? $data['result']['addressComponent']['province'] : null,
            'countyCode'   => isset($data['result']['cityCode']) ? $data['result']['cityCode'] : null,
        )));
    }
}
