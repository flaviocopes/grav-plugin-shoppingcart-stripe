<?php
namespace Grav\Plugin\ShoppingCart;

use RocketTheme\Toolbox\Event\Event;
use Omnipay\Omnipay;

/**
 * Class GatewayStripe
 * @package Grav\Plugin\ShoppingCart
 */
class GatewayStripe extends Gateway
{
    protected $name = 'stripe';

    /**
     * Handle paying via this gateway
     *
     * @param Event $event
     *
     * @event onShoppingCartSaveOrder signal to save the order
     * @event onShoppingCartReturnOrderPageUrlForAjax signal to return the order page and exit, for AJAX processing
     *
     * @return mixed|void
     */
    public function onShoppingCartPay(Event $event)
    {
        if (!$this->isCurrentGateway($event['gateway'])) { return false; }

        $order = $this->getOrderFromEvent($event);

        $amount = $order->amount;
        $currency = $this->grav['config']->get('plugins.shoppingcart.general.currency');
        $description = $this->grav['config']->get('plugins.shoppingcart.payment.methods.stripe.description');

        $token = $order->extra['stripeToken'];
        $secretKey = $this->grav['config']->get('plugins.shoppingcart.payment.methods.stripe.secretKey');

        $gateway = Omnipay::create('Stripe');
        $gateway->setApiKey($secretKey);

        try {
            $response = $gateway->purchase([
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'token' => $token])->send();

            if ($response->isSuccessful()) {
                // mark order as complete
                $this->grav->fireEvent('onShoppingCartSaveOrder', new Event(['gateway' => $this->name, 'order' => $order]));
                $this->grav->fireEvent('onShoppingCartReturnOrderPageUrlForAjax', new Event(['gateway' => $this->name, 'order' => $order]));
            } elseif ($response->isRedirect()) {
                $response->redirect();
            } else {
                // display error to customer
                throw new \RuntimeException("Payment not successful: " . $response->getMessage());
            }
        } catch (\Exception $e) {
            // internal error, log exception and display a generic message to the customer
            throw new \RuntimeException('Sorry, there was an error processing your payment: ' . $e->getMessage());
        }
    }
}