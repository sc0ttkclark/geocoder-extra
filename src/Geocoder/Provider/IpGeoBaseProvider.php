<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider;

use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Exception\NoResult;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class IpGeoBaseProvider extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    const ENDPOINT_URL = 'http://ipgeobase.ru:7020/geo?ip=%s';

    /**
     * {@inheritDoc}
     */
    public function geocode($address)
    {
        if (!filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The IpGeoBaseProvider does not support Street addresses.');
        }

        // This API does not support IPv6
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new UnsupportedOperation('The IpGeoBaseProvider does not support IPv6 addresses.');
        }

        if ('127.0.0.1' === $address) {
            return array($this->getLocalhostDefaults());
        }

        $query = sprintf(self::ENDPOINT_URL, $address);

        $content = $this->getAdapter()->get($query)->getBody();

        try {
            $xml = new \SimpleXmlElement($content);
        } catch (\Exception $e) {
            throw new NoResult(sprintf('Could not execute query %s', $query));
        }

        $result = $xml->ip;

        return array(array_merge($this->getDefaults(), array(
            'latitude'     => (double) $result->lat,
            'longitude'    => (double) $result->lng,
            'city'         => (string) $result->city,
            'region'       => (string) $result->region,
            'countryCode'  => (string) $result->country,
            'zipcode'   => null,
            'regionCode'   => null,
        )));
    }

    /**
     * {@inheritDoc}
     */
    public function reverse($latitude, $longitude)
    {
        throw new UnsupportedOperation('The IpGeoBaseProvider is not able to do reverse geocoding.');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'ip_geo_base';
    }
}
