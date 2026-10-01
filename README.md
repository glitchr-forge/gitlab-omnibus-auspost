# omnibus/auspost

Australia Post for [glitchr/omnibus](https://github.com/glitchr-studio/omnibus): prices,
shipments with their labels, tracking and cancellations - the Shipping and Tracking API
(basic auth with the API key, the charge account in a header).

```yaml
omnibus:
    gateways:
        auspost:
            factory: auspost
            options:
                api_key: '%env(AUSPOST_API_KEY)%'
                password: '%env(AUSPOST_PASSWORD)%'
                account_number: '%env(AUSPOST_ACCOUNT)%'   # the 10-digit charge account
                sandbox: true
                rates: [...]                               # optional: configured prices instead of /prices/items
```

The service is the product id (PP Parcel Post, EXP Express Post, PTI8 International Standard,
ECM8 International Express...). Shipment options: `sender_state` and `recipient_state`
(VIC, NSW...), `authority_to_leave`, `layout` (A4-1pp, A4-4pp, THERMAL-LABEL-A6-1PP),
`description` and `classification` (customs). No pickup points: parcel lockers and post offices
are addressed as recipient addresses.

Credentials: a business account with a charge account, then the Shipping and Tracking API at
[developers.auspost.com.au](https://developers.auspost.com.au) gives the key and password
(test credentials first).

Built from Australia Post's published API documentation and tested on recorded answers; not yet
run against the test environment: that needs the credentials above.

License: LGPL-3.0-or-later.
