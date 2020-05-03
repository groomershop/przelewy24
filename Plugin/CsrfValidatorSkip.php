<?php
namespace Dialcom\Przelewy\Plugin;

/**
 * Class CsrfValidatorSkip
 * @package Dialcom\Przelewy\Plugin
 */
class CsrfValidatorSkip
{
    /**
     * Prevents applying additional CSRF validation when module is "przelewy".
     *
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        if ('przelewy' === $request->getModuleName()) {
            return; // Skip CSRF check
        }
        $proceed($request, $action); // Proceed Magento 2 core functionalities
    }
}
