<?php

namespace Drupal\asu_user\EventSubscriber;

use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Service\CasHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserPreLoginSubscriber.
 *
 * @package Drupal\asu_user\EventSubscriber
 */
class UserPreLoginSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Static class constant => method on this class.
      CasHelper::EVENT_PRE_LOGIN => 'onUserPreLogin',
    ];
  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   * 
   */
  public function onUserPreLogin(CasPreLoginEvent $event) {
    $account = $event->getAccount();
    $propertyBag = $event->getCasPropertyBag();
    $cas_username = $propertyBag->getUsername();
    
    // If this account signed with the asu account.
    $employee = FALSE;
    if ($cas_username) {
      $asu_profile = _asu_user_get_solr_profile_record($cas_username);
      $affiliations = isset($asu_profile['affiliations']) ? $asu_profile['affiliations'] : [];
      // Search for the "Employee" affilliation and set the employ role for that account.
      if (in_array('Employee', $affiliations)) {
        $employee = TRUE;
      }
    }
    if ($employee && !$account->hasRole('employee')) {
      $account->addRole('employee');
      // We save because the cas altough it saves the account after this event,
      // it doesnt add the ability to modify the variables.
      $account->save();
    }
    if (!$employee && $account->hasRole('employee')) {
      $account->removeRole('employee');
      $account->save();
    }
  }

}