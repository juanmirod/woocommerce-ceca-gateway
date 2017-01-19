# WOOCOMMERCE CECA GATEWAY

This plugin allow you to add a CECABANK gateway to your woocommerce store.

UPDATED 19/01/2017: Fixed problem with URL_NOK thanks to contributions from [@grisendo](https://github.com/grisendo) and [@grabielmarma](https://twitter.com/gabrielmarma) I have confirmation that this works, although the woocommerce functions seems to be deprecated and you may need to use the new one in recent versions (https://docs.woocommerce.com/wc-apidocs/function-wc_get_page_id.html) Many thanks to the colaborators, you keep this repo useful for others!

# INSTALLATION INSTRUCTIONS

- Copy the plugin folder to wp-content/plugins/woocommerce-ceca-gateway
- Go to wp-admin/plugins and activate the plugin
- Click on Woocommerce/settings/Checkout and on the top in "Pasarela CECABANK"  and add your gateway specific password and ids. (This you can find in your CECABANK admin panel)
- The comunication address for the TPV is should be something like: 

```
    http://yourdomain.com/?wc-api=wc_gateway_ceca
    
    or
    
    https://yourdomain.com/?wc-api=wc_gateway_ceca
```

- Click on Enable (Permitir pasarela de pago CECABANK)
- You can test your gateway in sandbox mode if you check the second option (Modo de prueba)
- Once you hace tested the gateway with a testing card, you should test is in normal mode with a real card number. (You can return the payment in your CECABANK administrator panel)

Enjoy!
