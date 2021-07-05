# Instalacja wtyczki Przelewy24
### Instalacja manulana- bez użycia composer'a

* katalog "Dialcom" należy wypakować do katalogu Magento app/code
* aktywujemy moduły:
```

            php bin/magento module:enable Dialcom_Przelewy
```
* następnie w konsoli uruchamiamy polecenie:

```

            php bin/magento setup:upgrade
```

* w kolejnym kroku uruchamiamy polecenie "setup:static-content:deploy" z kodami języków jako argumenty:

```

            php bin/magento setup:static-content:deploy pl_PL en_US
```

## Wymagania wtyczki i sklepu ##

- Apache 2.2+
- MySQL 5.6+
- PHP 5.6.x
- PHP 5.5.x, where x is 22 or greater
- cURL
- SOAP
