<?php

require __DIR__ . '/UPnP.php';

$upnp = new UPnP();
var_dump($upnp->routerIPAddress ());
var_dump($upnp->getExternalIPAddress());
var_dump($upnp->addPortMapping (8080, 8080, '192.168.10.4'));
var_dump($upnp->deletePortMapping (8080));
