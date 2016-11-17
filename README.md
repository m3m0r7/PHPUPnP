#UPnP by PHP

## UPnP::routerIPAddress
ルーターのIPアドレスを取得します。

```
$upnp = new UPnP();
echo $upnp->routerIPAddress ();
```

上記のように実行すると `192.168.1.1` や `192.168.10.1` など返ってきます。

## UPnP::getExternalIPAddress

現在のIPアドレスを取得します。

```
$upnp = new UPnP();
echo $upnp->getExternalIPAddress ();
```

上記のように実行すると、取得可能です。

## UPnP::addPortMapping

ポートマッピングに新しく追加します。

```
$upnp = new UPnP();
$upnp->addPortMapping (8080, 8080, '192.168.10.4')
```


## UPnP::deletePortMapping

ポートマッピングを削除します。

```
$upnp = new UPnP();
$upnp->deletePortMapping (8080)
```
