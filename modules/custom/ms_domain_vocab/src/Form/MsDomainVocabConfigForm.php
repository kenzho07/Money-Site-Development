<?php

namespace Drupal\ms_domain_vocab\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainLoader;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class MsDomainVocabConfigForm.
 *
 * @package Drupal\ms_domain_vocab\Form
 */
class MsDomainVocabConfigForm extends ConfigFormBase {

  /**
   * Drupal\domain\DomainLoader definition.
   *
   * @var \Drupal\domain\DomainLoader
   */
  protected $domainLoader;

  /**
   * 
   */
  // protected $vocabEntity;

  /**
   * 
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainLoader $domain_loader) {
    parent::__construct($config_factory);
    $this->domainLoader = $domain_loader;
    // $this->vocabEntity = $vocab_handler;
  }

  /**
   * 
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), $container->get('domain.loader')
    );
  }

  /**
   * 
   */
  protected function getEditableConfigNames() {
    return [
      'ms_domain_vocab.settings',
    ];
  }

  /**
   * 
   */
  public function getFormId() {
    return 'ms_domain_vocab_config_form';
  }

  /**
   * 
   */
  public function getVocabList() {
    $vocabName = Vocabulary::loadMultiple();
    $vocabNameList = [];
    foreach ($vocabName as $vid => $vocab) {
      $vocabNameList[$vid] = $vocab->get('name');
    }

    return $vocabNameList;
  }

  /**
   * 
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ms_domain_vocab.settings');
    $domains = $this->domainLoader->loadMultipleSorted();
    $vocabList = $this->getVocabList();

    // Specify default value.
    array_unshift($vocabList, '- None -');
    
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $hostname = $domain->get('name');
      $form[$domainId] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Select Vocab for "@domain"', ['@domain' => $hostname]),
      ];

      $form[$domainId][$domainId . '_site'] = [
        '#title' => $this->t('Vocab for domain'),
        '#type' => 'select',
        '#options' => $vocabList,
        '#default_value' => (NULL !== $config->get($domainId . '_site')) ? $config->get($domainId . '_site') : $vocabList[0],
      ];
    }

    if (count($domains) === 0) {
      $form['ms_domain_vocab_message'] = [
        '#markup' => $this->t('Zero domain records found. Please @link to create the domain.', ['@link' => $this->l($this->t('click here'), Url::fromRoute('domain.admin'))]),
      ];
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * 
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * 
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $domains = $this->domainLoader->loadMultipleSorted();
    $config = $this->config('ms_domain_vocab.settings');
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $config->set($domainId . '_site', $form_state->getValue($domainId . '_site'));
    }
    $config->save(); 
  }
}
