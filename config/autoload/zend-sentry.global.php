<?php
/**
 * ZendSentry Global Configuration
 *
 * If you have a ./config/autoload/ directory set up for your project, you can
 * drop this config file in it, remove the .dist extension add your configuration details.
 */
$settings = array(
    /**
     * Turn ZendSentry off or on as a whole package
     */
    'use-module' => true,

    /**
     * Attach a generic logger event listener so you can log custom messages from anywhere in your app
     */
    'attach-log-listener' => true,

    /**
     * Register the Sentry logger as PHP error handler
     */
    'handle-errors' => true,

    /**
     * Should the previously registered error handler be called as well?
     */
    'call-existing-error-handler' => true,

    /**
     * Which errors should be reported to sentry (bitmask), e. g. E_ALL ^ E_DEPRECATED
     * Defaults to -1 to report all possible errors (equivalent to E_ALL in >= PHP 5.4)
     */
    'error-reporting' => -1,

    /**
     * Register Sentry as shutdown error handler
     */
    'handle-shutdown-errors' => true,

    /**
     * Register the Sentry logger as PHP exception handler
     */
    'handle-exceptions' => true,

    /**
     * Should the previously registered exception handler be called as well?
     */
    'call-existing-exception-handler' => true,

    /**
     * Should exceptions be displayed on the screen?
     */
    'display-exceptions' => true,
    
    /**
     * If Exceptions are displayed on screen, this is the default message
     */
    'default-exception-message' => 'Incident ID: %s',
    
    /**
     * If Exceptions are displayed on screen, this is the default message in php cli mode
     */
    'default-exception-console-message' => "Oh no. Something went wrong, but we have been notified.\n",

    /**
     * Should Sentry also log javascript errors?
     */
    'handle-javascript-errors' => true,

    /**
     * Set raven config options here.
     * Raven has sensible defaults set in Raven_Client, if you need to override them, this is where you can do it.
     */
    'raven-config' => array(),
);

/**
 * You do not need to edit below this line
 */
return array(
    'zend-sentry' => $settings,
);
