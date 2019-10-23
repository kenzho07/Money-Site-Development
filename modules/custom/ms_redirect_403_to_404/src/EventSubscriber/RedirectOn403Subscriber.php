<?php

namespace Drupal\ms_redirect_403_to_404\EventSubscriber;

use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a subscriber to set the properly exception.
 */
class RedirectOn403Subscriber implements EventSubscriberInterface {

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  public function __construct(AdminContext $admin_context, AccountProxyInterface $current_user) {
    $this->adminContext = $admin_context;
    $this->currentUser = $current_user;
  }

  /**
   * Set the properly exception for event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The response for exception event.
   */
  public function onAccessDeniedException(GetResponseForExceptionEvent $event) {
    if ($event->getException() instanceof AccessDeniedHttpException) {
      $is_admin = $this->adminContext->isAdminRoute();
      if ($is_admin && !$this->currentUser->hasPermission('access 403 page')) {
        $event->setException(new NotFoundHttpException());
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = array('onAccessDeniedException', 50);
    return $events;
  }

}
