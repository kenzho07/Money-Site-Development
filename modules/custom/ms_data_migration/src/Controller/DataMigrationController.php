<?php

namespace Drupal\ms_data_migration\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Updates backlinks from WP API
 */
class DataMigrationController{

  private $postDefaultValues;
  private $defaultConfig;
  private $uid;

  /**
   * Constructor
   */
  public function __construct() {
    $this->loadDefaultConfig();
  }

  public function migrateData() : JsonResponse {
    $storage = \Drupal::entityTypeManager()->getStorage('domain');
    $domains = $storage->loadMultipleSorted();
    $subsite = $this->defaultConfig;
    $upsertData = $upsertCategories = false;
    $protocol = 'http://';
    if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))) {
      $protocol = 'https://';
    }

    foreach($domains as $domain){   
      $api_base_url = $domain->getHostname();
      // $api_base_url = 'localhost:8888/touristsecrets';
      if($api_base_url == 'wp-tourist.ieplsg.com'){
        $domainId = $domain->id();     
        $category_url = (NULL != $subsite->get($domainId.'_category_url'))?$subsite->get($domainId.'_category_url'):$subsite->get('default.default_category_url');
        // $results = $this->getDataFromApi($api_base_url.$subsite->get($domainId.'_posts_url'));
        // $upsertData = $results ? $this->upsertBacklinks($results, $subsite->get('default.subsites.field_mappings')) : false; 
        $categories = $this->getDataFromApi($protocol.$api_base_url.$category_url);
        $upsertCategories = $categories ? $this->upsertCategories($categories, $subsite->get('default.subsites.category_mappings'), $subsite->get('default.subsites.bundle')) : false;
      }
    }

    return new JsonResponse(
      [
        // 'post_migration' => $upsertData,
        'category_migration' => $upsertCategories,
        'method' => 'GET'
      ]
    );
  }

  /***
   * Combines default values with the data from the post entry
   * @param array $data post entry
   * @param array $fieldMappings
   * @return array of combined default and fetched entry
   */
  private function processData(array $data, array $fieldMappings) : array {
    $processedData = $this->postDefaultValues + $this->mapData($data, $fieldMappings);
    return $processedData;
  }

  /***
   * Upserts the nodes of respective backlinks.
   * If a post is already existing, update it with new values.
   * Otherwise, create new node.
   * @param array $results
   * @param array $fieldMappings
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function upsertBacklinks(array $results, array $fieldMappings) : bool {
    // dump($results);
    // dump($fieldMappings);
    // die;
    foreach ($results as $data) {
      if($data['post_title']!=""){
        $nodeData = $this->processData($data, $fieldMappings);
        $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($nodeData['vid']);
  
        if (! $node) {
          $node = Node::create($nodeData);
          } else {
            $node->content = $nodeData['content'];
            $node->name = $nodeData['name'];
            $node->title = $nodeData['title'];
            $node->url = $nodeData['url'];
          }
          $node->save();
        }
      }
      return true;
      
  }

  /***
   * @param array $categories
   * @param array $categoryMappings
   * @param string $bundle
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function upsertCategories(array $categories, array $categoryMappings, string $bundle) : bool {
    foreach ($categories as $category) {
      $nodeData = $this->mapData($category, $categoryMappings);
      $nodeData['vid'] = $bundle;
      $nodeData['revision_id'] = $category['term_id'];
      $termResult = Term::load($category['term_id']);

      if (! $termResult) {
        $term = Term::create($nodeData);
      } 
      else {
        $term = $termResult;
        $term->name = $category['name'];
      }
      
      $term->set('parent', $category['parent']);
      $term->save();
      
    }
    return true;
  }

  /***
   * Gets json data from the WP endpoint and decodes for processing
   * @param string $url
   * @return array|bool
   */
  private function getDataFromApi(string $url) : array
  {
    $response = $this->getResponse($url);
    $data = Json::decode($response);

    if (! $data) {
      return false;
    }

    return $data;
  }

  /***
   * Loads default config from data_migration.settings.yml
   * and loads default values for the post entries.
   */
  private function loadDefaultConfig() : void {
    $this->defaultConfig = \Drupal::config('ms_data_migration.settings');
    $this->uid = $this->defaultConfig->get('uid') ?? "";
    $this->postDefaultValues = [
      'uid' => $this->uid,
      'type' => 'backlinks_related_articles',
      'status' => 1,
      'body_format' => 'full_html'
    ];
  }

  /**
   * Gets json response from the API
   * @param string $url
   * @return string
   */
  private function getResponse(string $url) : string {
    try {
      $options = array(
        'method' => 'GET',
        'timeout' => 15,
        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
      );

      $response = \Drupal::httpClient()->get($url, $options);
      $json = (string) $response->getBody();
      if (! $json) {
        return false;
      }
    }
    catch (RequestException $e) {
      return false;
    }

    return $json;
  }

  /**
   * Maps the fetched data into keys recognizable by drupal db
   * @param array $data
   * @param array $fieldMappings
   * @return array
   */
  private function mapData(array $data, array $fieldMappings) : array {
    $mappedData = [];
    foreach ($fieldMappings as $key => $value) {
      $mappedData[$value] = $data[$key];
    }

    return $mappedData;
  }

  /***
   * Allow access if token from URL is valid
   * @param string $token
   * @return AccessResult
   */
  public function checkValidToken(string $token) : AccessResult {

    $key = \Drupal::state()->get('system.cron_key');
    $validToken = $key === $token;

    return AccessResult::allowedIf($validToken);
  }
}
