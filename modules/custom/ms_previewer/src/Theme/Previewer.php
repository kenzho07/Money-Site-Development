<?php

namespace Drupal\ms_previewer\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class Previewer implements ThemeNegotiatorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */

  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->currentUser   = $current_user;
    $this->configFactory = $config_factory;
}
  /**
   * The function applies() determines if it wants to set the 
   * active theme. Return TRUE to denote that Previewer wants to set
   * the domain specific theme. determineActiveTheme() will be called to
   * ask for the theme's name.
   */
  
  public function applies(RouteMatchInterface $route_match) {
    if($this->currentUser->hasPermission('View unpublished content on assigned domains')){
      return TRUE;
    }
    return FALSE;
  }

  /**
   * The function determineActiveTheme() is responsible
   * for returning the name of the theme that is to be used.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    
    // Allowed content type(s)
    $allowed_content_types = ['site_posts'];
    
    // Fetch domain specific theme settings
    $config = $this->configFactory->get('domain_theme_switch.settings');
    
    // Fetch default portal admin theme
    $theme = $config->get('portal_dev_ops_xyz_admin');
    
    // Fetch current node
    $node = \Drupal::routeMatch()->getParameter('node');
    if(isset($node)) {
      if (!$node instanceof \Drupal\node\NodeInterface) {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $node = $node_storage->load($node);
      }
      $content_type = $node->bundle(); //$node->type->getValue(); 
      $current_path = \Drupal::service('path.current')->getPath();
      $path_args = explode('/', $current_path);
      if(in_array($content_type, $allowed_content_types) && (isset($path_args[3]) != 'edit')){
      
      // Fetch associated domain source with the node
      $domain_access = $node->field_domain_access->getValue();

      // Fetch theme associated with node assiciated domain source
      $theme = $config->get($domain_access[0]['target_id'].'_site');
      return $theme;
      }
     
    }
  }
}