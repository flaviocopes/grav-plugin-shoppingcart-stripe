<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;

/**
 * Class ShoppingcartStripePlugin
 * @package Grav\Plugin
 */
class ShoppingcartStripePlugin extends Plugin
{
    protected $plugin_name = 'shoppingcart-stripe';

    protected $gateway;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     */
    public function onTwigSiteVariables()
    {
        $this->grav['assets']->addJs('plugin://' . $this->plugin_name . '/gateways/stripe/script.js');
        $this->grav['assets']->addJs('https://checkout.stripe.com/checkout.js');
    }

    /**
     * Enable search only if url matches to the configuration.
     */
    public function onPluginsInitialized()
    {
        require_once __DIR__ . '/vendor/autoload.php';

        $this->config->set('plugins.shoppingcart', array_replace_recursive($this->config->get('plugins.shoppingcart'), $this->config->get('plugins.shoppingcart-stripe')));

        if (!$this->isAdmin()) {
            // Site
            $this->enable([
                'onTwigSiteVariables'          => ['onTwigSiteVariables', 0],
                'onShoppingCartPay'            => ['onShoppingCartPay', 0],
            ]);
        }
    }

    /**
     *
     */
    protected function requireGateway()
    {
        $path = realpath(__DIR__ . '/../shoppingcart/classes/gateway.php');
        if (!file_exists($path)) {
            $path = realpath(__DIR__ . '/../grav-plugin-shoppingcart/classes/gateway.php');
        }
        require_once($path);
    }

    /**
     *
     */
    public function getGateway()
    {
        if (!$this->gateway) {
            $this->requireGateway();
            require_once __DIR__ . '/gateways/stripe/gateway.php';
            $this->gateway = new ShoppingCartGatewayStripe();
        }

        return $this->gateway;
    }

    /**
     * @param $event
     */
    public function onShoppingCartPay($event)
    {
        $this->getGateway()->onShoppingCartPay($event);
    }
}