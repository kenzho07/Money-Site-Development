<?php

namespace Drupal\ms_data_migration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainLoader;

/**
 * Class MsDataMigrateApiConfigForm.
 *
 * @package Drupal\ms_data_migration\Form
 */
class MsDataMigrateApiConfigForm extends ConfigFormBase {

  /**
   * Drupal\domain\DomainLoader definition.
   *
   * @var \Drupal\domain\DomainLoader
   */
  protected $domainLoader;

  /**
   * 
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainLoader $domain_loader) {
    parent::__construct($config_factory);
    $this->domainLoader = $domain_loader;
    
  }

  /**
   * 
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('domain.loader')
    );
  }

  /**
   * 
   */
  protected function getEditableConfigNames() {
    return [
      'ms_data_migration.settings',
    ];
  }

  /**
   * 
   */
  public function getFormId() {
    return 'ms_data_migration_api_config_form';
  }

  /**
   * Form builder.
   *
   * @param array $form
   *   An associative array containing the structure of the form. 
   * @param FormStateInterface $form_state
   *   An associative array containing the state of the form. 
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TO DO
    // 1) Disable default settings for safer side
    // 2) Apply field specific validations on form fields (to validate API endoints, may be at later stage)
    // To  get default settings for ms_data_migration
    $config = $this->config('ms_data_migration.settings');
  
    $form['default'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configure API settings - Default'),
    ];
    $form['default']['default_run_interval'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return Interval'),
      '#default_value' => $config->get('default.default_run_interval'),
    ];

    $form['default']['default_default_uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default UID'),
      '#default_value' => $config->get('default.default_default_uid'),
    ];

    $form['default']['default_category_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint - Get Post Category'),
      '#default_value' => $config->get('default.default_category_url'),
    ];

    $form['default']['default_create_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint - Create Post'),
      '#default_value' => $config->get('default.default_create_url'),
    ];
  
    $form['default']['default_update_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint - Update Post'),
      '#default_value' => $config->get('default.default_update_url'),
    ];  

    $form['default']['default_posts_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Endpoint - Get Posts'),
      '#default_value' => $config->get('default.default_posts_url'),
    ];
   
    $form = $this->create_domain_specific_forms($form);

    return parent::buildForm($form, $form_state);
  }

  /**
   * 
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Form submission.
   *
   * @param array $form
   *   An associative array containing the structure of the form. 
   * @param FormStateInterface $form_state
   *   An associative array containing the state of the form. 
   * @return void
   *   Redirects to same form with success message.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $domains = $this->domainLoader->loadMultipleSorted();
    $values = $form_state->getValues();

    \Drupal::logger('ms_data_migration')->debug('Form values: <pre>@values</pre>',
      array(
            '@values' => print_r($values, TRUE),
          ));
    // To get config settings
    $config = $this->config('ms_data_migration.settings');
    // To set default settings configured from admin
    $config->set('default.default_run_interval', $values['default_run_interval'])
            ->set('default.default_default_uid', $values['default_default_uid'])
            ->set('default.default_posts_url', $values['default_posts_url'])
            ->set('default.default_update_url', $values['default_update_url'])
            ->set('default.default_create_url', $values['default_create_url'])
            ->set('default.default_category_url', $values['default_category_url']);

    // To set domain specific settings configured from admin
      foreach($domains as $domain){   
        $domainId = $domain->id();     
            $config->set($domainId.'_run_interval', $values[$domainId.'_run_interval'])
            ->set($domainId.'_default_uid', $values[$domainId.'_default_uid'])
            ->set($domainId.'_posts_url', $values[$domainId.'_posts_url'])
            ->set($domainId.'_update_url', $values[$domainId.'_update_url'])
            ->set($domainId.'_create_url', $values[$domainId.'_create_url'])
            ->set($domainId.'_category_url', $values[$domainId.'_category_url']);
      }
      $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Domain speific Form creator.
   *
   * @param array $form
   *   An associative array containing the structure of the form. 
   * @return array
   *   The form structure.
   */
  private function create_domain_specific_forms($form){
    $config = $this->config('ms_data_migration.settings');
    $domains = $this->domainLoader->loadMultipleSorted();
    \Drupal::logger('ms_data_migration')->debug('Domain values: <pre>@domains</pre>',
      array(
            '@domains' => print_r($domains, TRUE),
          ));
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $hostname = $domain->get('name');
      $form[$domainId] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Configure API settings - "@domain"', ['@domain' => $hostname]),
      ];
      $form[$domainId][$domainId.'_run_interval'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Return Interval'),
        '#default_value' => $config->get($domainId.'_run_interval'),
      ];
  
      $form[$domainId][$domainId.'_default_uid'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default UID'),
        '#default_value' => $config->get($domainId.'_default_uid'),
      ];
  
      $form[$domainId][$domainId.'_category_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('API Endpoint - Get Post Category'),
        '#default_value' => $config->get($domainId.'_category_url'),
      ];
      
      $form[$domainId][$domainId.'_create_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('API Endpoint - Create Post'),
        '#default_value' => $config->get($domainId.'_create_url'),
      ];

      $form[$domainId][$domainId.'_update_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('API Endpoint - Update Post'),
        '#default_value' => $config->get($domainId.'_update_url'),
      ];
  
      $form[$domainId][$domainId.'_posts_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('API Endpoint - Get Posts'),
        '#default_value' => $config->get($domainId.'_posts_url'),
      ];
      
    }
    return $form;
  }
}