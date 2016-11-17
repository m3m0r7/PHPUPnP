<?php

/**
 * UPnP by PHP
 *
 * tested devices
 *  - Aterm (NEC)
 *
 * @author @memory-agape
 */
class UPnP {

    /**
     * TCP String
     * @var string
     */
    const TCP = 'TCP';

    /**
     * UDP String
     * @var string
     */
    const UDP = 'UDP';

    /**
     * set Location data
     * @var string
     */
    private $_locationData;

    /**
     * set Device Type
     * @var string
     */
    private $_deviceType;

    /**
     * set Service Type
     * @var string
     */
    private $_serviceType;

    /**
     * set Control URL
     * @var string
     */
    private $_controlURL;

    /**
     * set router IP
     * @var string
     */
    private $_router;

    /**
     * set Router Model Name
     * @var string
     */
    private $_modelName = [];

    /**
     * Search router and initial settings.
     */
    public function __construct () {

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, IPPROTO_IP, IP_MULTICAST_IF, 0);
        socket_set_option($socket, IPPROTO_IP, IP_MULTICAST_LOOP, 1);
        $contents = 'M-SEARCH * HTTP/1.1' . "\n"
                  . 'MX: 3' . "\n"
                  . 'HOST: 239.255.255.250:1900' . "\n"
                  . 'MAN: "ssdp:discover"' . "\n"
                  . 'ST: upnp:rootdevice' . "\n"
                  . "\n";

        socket_sendto($socket, $contents, strlen($contents), 0, '239.255.255.250', '1900');
        socket_recvfrom($socket, $buffer, 1024, 0, $remoteAddr, $remotePort);

        if (preg_match('/location\s*:\s*([^\n]+)\n/is', $buffer, $matches)) {
            $router = parse_url(trim($matches[1]));
            $this->_router = $router['host'] . ':' . $router['port'];
            $this->_locationData = file_get_contents(trim($matches[1]));
                    file_put_contents(__DIR__ . '/result.txt', $this->_locationData);
            if (preg_match_all('/<deviceList>.+<deviceType>([^<]+)<\/deviceType>/is', $this->_locationData, $matches)) {
                $this->_deviceType = $matches[1];
            }

            if (preg_match_all('/<deviceList>.+<serviceType>([^<]+)<\/serviceType>/is', $this->_locationData, $matches)) {
                $this->_serviceType = $matches[1];
            }

            if (preg_match_all('/<deviceList>.+<controlURL>([^<]+)<\/controlURL>/is', $this->_locationData, $matches)) {
                $this->_controlURL = $matches[1];
            }

            if (preg_match_all('/<deviceList>.+<modelName>([^<]+)<\/modelName>/is', $this->_locationData, $matches)) {
                $this->_modelName = $matches[1];
            }

            return true;
        }
        return false;
    }

    /**
     * Get Router IP
     * @return string router IP
     */
    public function routerIPAddress () {
        return explode(':', $this->_router)[0];
    }

    /**
     * Get External IP
     * @return string external IP
     */
    public function getExternalIPAddress () {
        $received = $this->command('GetExternalIPAddress', '<m:GetExternalIPAddress xmlns:m="' . $this->_serviceType[0] . '"></m:GetExternalIPAddress>');
        if (preg_match('/<NewExternalIPAddress>([^<]+)<\/NewExternalIPAddress>/', $received, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Add Port to UPnP
     *
     * @param int $externalPort            set external ip address
     * @param int $internalPort            set transfer ip address
     * @param string $internalIPAddress    set IP
     * @param string $protocol             set TCP or UDP
     * @param int $timeout                 set timeout
     * @param string $description          set description
     * @return bool
     */
    public function addPortMapping ($externalPort, $internalPort, $internalIPAddress, $protocol = self::TCP, $timeout = 0, $description = '') {
        $received = $this->command('AddPortMapping', '<m:AddPortMapping xmlns:m="' . $this->_serviceType[0] . '">
            <NewRemoteHost></NewRemoteHost>
            <NewExternalPort>' . $externalPort . '</NewExternalPort>
            <NewProtocol>' . $protocol . '</NewProtocol>
            <NewInternalPort>' . $internalPort . '</NewInternalPort>
            <NewInternalClient>' . $internalIPAddress . '</NewInternalClient>
            <NewEnabled>1</NewEnabled>
            <NewPortMappingDescription>' . $description . '</NewPortMappingDescription>
            <NewLeaseDuration>' . $timeout . '</NewLeaseDuration>
        </m:AddPortMapping>');
        if (preg_match('/<m:AddPortMappingResponse xmlns:m="' . $this->_serviceType[0] . '">[^<]+<\/m:AddPortMappingResponse>/s', $received)) {
            return true;
        }
        return false;
    }

    /**
     * Delete port
     *
     * @param  int $externalPort    set external port
     * @param  string $protocol     set TCP or UDP
     * @return bool
     */
    public function deletePortMapping ($externalPort, $protocol = self::TCP) {
        $received = $this->command('DeletePortMapping', '<m:DeletePortMapping xmlns:m="' . $this->_serviceType[0] . '">
            <NewRemoteHost></NewRemoteHost>
            <NewExternalPort>' . $externalPort . '</NewExternalPort>
            <NewProtocol>' . $protocol . '</NewProtocol>
        </m:DeletePortMapping>
        ');
        if (preg_match('/<m:DeletePortMappingResponse xmlns:m="' . $this->_serviceType[0] . '">[^<]+<\/m:DeletePortMappingResponse>/s', $received)) {
            return true;
        }
        return false;
    }

    /**
     * Send command
     *
     * @param  string $command set command
     * @param  string $content set send contents
     * @return string received server stream
     */
    private function command ($command, $content) {
        $socket = stream_socket_client('tcp://' . $this->_router);
        $content = '<?xml version="1.0"?>
            <SOAP-ENV:Envelope xmlns:SOAP-ENV:="http://schemas.xmlsoap.org/soap/envelope/" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
              <SOAP-ENV:Body>
                ' . $content . '
            </SOAP-ENV:Body>
          </SOAP-ENV:Envelope>';
        fwrite($socket, 'POST ' . $this->_controlURL[0] . ' HTTP/1.1' . "\r\n"
                      . 'Content-Type: text/xml; charset="utf-8"' . "\r\n"
                      . 'Host: ' . $this->_router . "\r\n"
                      . 'Content-Length: ' . strlen($content) . "\r\n"
                      . 'Connection: Close' . "\r\n"
                      . 'SOAPACTION: "' . $this->_serviceType[0] . '#' . $command . '"' . "\r\n"
                      . "\r\n"
                      . $content);

        return stream_get_contents($socket);
    }

}
