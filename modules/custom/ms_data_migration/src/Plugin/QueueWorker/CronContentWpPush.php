<?php

namespace Drupal\ms_data_migration\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Component\Utility\Html;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
// use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
// use Symfony\Component\DependencyInjection\ContainerInterface;
// use DataMigration\Services\AuthorDetailService;
/**
 * Publish Content from Drupal to WP.
 * 
 * @QueueWorker(
 *   id = "cron_content_wp_push",
 *   title = @Translation("Push Content To WP"),
 *   cron = {"time" = 5}
 * )
 */ 

const DRUPAL_PUBLIC_PATH = 'public://';
const DRUPAL_BASE_PUBLIC_PATH = '/sites/default/files/';
const WP_PUBLIC_PATH = '/wp-content/uploads/portal_files/';


class CronContentWpPush extends QueueWorkerBase
{
  /**
   * DataMigration\Services\AuthorDetailService definition.
   *
   * @var DataMigration\Services\AuthorDetailService
   */
  // protected $authorDetail;

  // public function __construct(array $configuration, $plugin_id, $plugin_definition, AuthorDetailService $author_detail)
  // {
  //   parent::__construct($configuration, $plugin_id, $plugin_definition);
  //   $this->authorDetail = $author_detail;
  // }
  
  

  // public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
  //   return new static(
  //     $configuration,
  //     $plugin_id,
  //     $plugin_definition,
  //     $container->get('data_migration.author_detail')
  //   );
  // }

 
  /**
   * {@inheritdoc}
   */
  public function processItem($node_id) {
    if($node_id) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $node = $node_storage->load($node_id);
      $http_client = \Drupal::httpClient();
      $domain_source = $node->field_domain_access->getValue();
      $state = $node->get('moderation_state')->value;
          
      if(NULL != $domain_source){
        if(($state == 'published') || ($state == 'archived')){
          $key = $domain_source[0]['target_id'];
          $domain = \Drupal::entityTypeManager()->getStorage('domain')->load($key);
          $config = \Drupal::config('ms_data_migration.settings');
          $api_config_posts_create = $config->get('default.default_create_url');
          $api_config_posts_update = $config->get('default.default_update_url');
          $api_config_posts_create_key = $config->get($key.'_create_url');
          $api_config_posts_update_key = $config->get($key.'_update_url');
          
          if(NULL != $api_config_posts_create_key){
            $api_config_posts_create = $api_config_posts_create_key;
          }
          if(NULL != $api_config_posts_update_key){
            $api_config_posts_update = $api_config_posts_update_key;
          }
          $protocol = 'http://';
          if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))) {
            $protocol = 'https://';
          }
          $api_base_url = $domain->getHostname();
          // $api_base_url = 'localhost:8888/touristsecrets';
          $api_endpoint = $protocol.$api_base_url;
          $api_payload = $meta_tag_details = [];
          $api_action = $tags_string = $p_cat_name = '';
          // Fetch categories for current node
          $p_categories = $s_categories = $categories  = $tags_list = [];
          $p_category = $node->get('field_touristsecrets_com_primary')->getValue();
          $s_category = $node->get('field_touristsecrets_com')->getValue();
          // Primary category
          if(count($p_category) > 0){
            $p_categories = $this->getTermDetails($p_category);
            $p_cat_name = current($p_categories);
          }
          // Secondary categories  
          if(count($s_category) > 0){
            $s_categories = $this->getTermDetails($s_category);
          }
          $categories = array_merge($p_categories,  $s_categories);
          // For tags
          $tags = $node->get('field_tag')->getValue();
          if(count($tags) > 0){
            $tags_list = $this->getTermDetails($tags);
            $tags_string = implode(", ", $tags_list);
          }
          // Fetch url alias from current node id
          // $path = '/node/' . (int) $node->id();
          // $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
          // $path_alias = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode);
          $title = $node->getTitle();
          $path_alias = \Drupal::service('pathauto.alias_cleaner')->cleanString($title);
          // Parse body field to update image path for using relative path instead of absolute path
          $body = $this->imageProcessor($node->get('body')->value);
          $meta_tag_page_title = $title;
          $meta_tag_page_description = $body;          
          // Fetch meta tags fields [Page Title, Description]
          $meta_tags= $node->get('field_meta_tags')->value;
          $meta_tag_details = unserialize($meta_tags);
            if(is_array($meta_tag_details)){
              $token_service = \Drupal::token();
              // Replace the token for subject.
              if(isset($meta_tag_details['title'])){
                $meta_tag_page_title = $token_service->replace($meta_tag_details['title'], ['node' => $node]);
              }
              if(isset($meta_tag_details['description'])){
                $meta_tag_page_description = $token_service->replace($meta_tag_details['description'], ['node' => $node]);
              }
            }
            // Fetch feature image field & its attributes
            $featured_image_attributes = $node->field_featured_image->getValue(); 
            $featured_image_attributes_values = $featured_image_attributes[0]; 
            $file_storage = \Drupal::entityTypeManager()->getStorage('file');
            $featured_image = $file_storage->load($featured_image_attributes_values['target_id']);
            // Fetch WP author from ms_site_drupal_wp_users mapping table
            // Fetch WP author from ms_site_drupal_wp_users mapping table
            $database = \Drupal::database();
            $query = $database->query("SELECT wp_uid FROM {ms_site_drupal_wp_users} WHERE uid = :uid and site = :site and status = :status", [
              ':uid' => $node->getRevisionUserId(),
              ':site' => $domain->getHostname(),
              ':status' => 1,
            ]);;
            $wp_author_result = $query->fetchObject();
            $wp_author = $wp_author_result->wp_uid;
            // $wp_author = $this->author_detail->getLatestAuthor($node->getRevisionUserId(), $domain->getHostname());
            // $wp_author = \Drupal::service('data_migration.author_detail')->getLatestAuthor($node->getRevisionUserId(), $domain->getHostname());

            // Prepare API payload with required fields
            $api_payload = [
              'post_title' => $title,
              'post_content' => $body,
              'post_author' => $wp_author,
              'post_type' => 'post',
              'tags_input' => $tags_string,
              'post_category' =>  $categories,
              'meta_input' => [
                'slug' => $path_alias,
                'title' => $meta_tag_page_title,
                'description' => $meta_tag_page_description,
                'primary_category' => $p_cat_name
              ],
              'featured_image' => [
                'attachment' => [
                  'title' => ($featured_image_attributes_values['title'] != "")?$featured_image_attributes_values['title']:$featured_image_attributes_values['alt'],
                  'alt' => $featured_image_attributes_values['alt'],
                  'width' => $featured_image_attributes_values['width'],
                  'height' => $featured_image_attributes_values['height'],
                  'mime_type' => $featured_image->filemime->value
                ],
                'relative_path' => str_replace(DRUPAL_PUBLIC_PATH, WP_PUBLIC_PATH, $featured_image->getFileUri())
              ]
            ];      
            if ($node->get('field_wp_content_id')->isEmpty()) {
              $api_action = $api_config_posts_create;
              $status = 'publish';              
              $api_payload['post_status'] = $status;
            } else {
              $api_action = $api_config_posts_update;
              if($state == 'archived'){
                $status= 'draft';
              } else {
                $status= 'publish';
              }        
              $api_payload['post_status'] = $status;
              $api_payload['post_id'] = $node->get('field_wp_content_id')->value;
            }

          $request = $http_client
            ->post($api_endpoint . $api_action, [
              'json' => $api_payload
            ]);

          $result = json_decode($request->getBody());
          if ($request->getStatusCode() == 200) {
            if ($api_action == $api_config_posts_create) {
              $node->set('field_wp_content_id', [
                'value' => $result->post_id,
              ]);
              $node->save();
            }
            // Use below to sync folders on different servers (LOCAL to DEV)
            // $output = exec('rsync -rave "ssh -i /Users/ankitarora/Downloads/dev-cmsportal.pem" sites/default/files/* ec2-user@ec2-3-1-222-244.ap-southeast-1.compute.amazonaws.com:/var/www/html/sites/default/files/');
            // Use below to sync folders on different servers (DEV - VM to DEV - AWS)
            // $output = exec('rsync -rave "ssh -i /home/deviepldrupaladm/dev-cmsportal.pem" sites/default/files/* ec2-user@ec2-3-1-222-244.ap-southeast-1.compute.amazonaws.com:/var/www/wp_tourist/wp-content/uploads/portal_files/');
            // $output = exec('rsync -rave "ssh -i /home/deviepldrupaladm/dev-cmsportal.pem" --exclude-from='exclude-list.txt'  sites/default/files/* ec2-user@ec2-3-1-222-244.ap-southeast-1.compute.amazonaws.com:/var/www/wp_tourist/wp-content/uploads/portal_files/');
            // Use below to sync folders on same server (LOCAL to LOCAL)
            // $output = exec('rsync -av sites/default/files/* ../touristsecrets/wp-content/uploads/portal_files/');
            // Use below to sync folders on same server (DEV to DEV)
            $outpout = exec("rsync -av --exclude-from='exclude-list.txt' sites/default/files/* ../wp_tourist/wp-content/uploads/portal_files/");
            \Drupal::logger('ms_data_migration')->debug(
              $api_action . ' content: <pre>@wpid</pre> <pre>@nid</pre> <pre>@result</pre> <pre>@result2 </pre> <pre>@result3 </pre> ',
              array(
                '@wpid' => print_r($node->get('field_wp_content_id')->value, TRUE),
                '@nid' => print_r($node->id(), TRUE),
                '@result' => print_r($featured_image_attributes_values, TRUE),
                '@result2' => print_r($outpout, TRUE),
                '@result3' => print_r($api_payload, TRUE),
              )
            );
          }
        }
      }      
    }
  }
/**
 * To convert abosulte url from body to relative url of image paths
 */
  private function imageProcessor($body) {
      $result =  $body;
      // if (stristr($body, 'data-entity-type="file"') !== FALSE) {
        $dom = Html::load($body);
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
          // Change the value of an attribute based on the current value.
          if(NULL != $image->getAttribute('data-entity-uuid')) {
            $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $image->getAttribute('data-entity-uuid'));
            if ($file instanceof FileInterface) {             
              $style = ImageStyle::load($image->getAttribute('data-image-style'));
              $uri = $style->buildUri($file->getFileUri());
              $image->setAttribute('src', str_replace(DRUPAL_PUBLIC_PATH, WP_PUBLIC_PATH, $uri));
            }
          } else {
            $image->setAttribute('src', str_replace($GLOBALS['base_url'].DRUPAL_BASE_PUBLIC_PATH, WP_PUBLIC_PATH, $image->getAttribute('src')));
          }
        }
        // Save the revised HTML
        $result = $dom->saveHTML();
      // } 
      return $result;  
  }

  /**
   * To list out all terms with all its details (name specifically)
   */
  private function getTermDetails($terms){
    foreach($terms as $term){
        $term_val = \Drupal\taxonomy\Entity\Term::load($term['target_id']);
        \Drupal::logger('ms_data_migration.terms')->debug(
          'content: <pre>@wpid</pre><pre>@wpid2</pre>',
          array(
            '@wpid' => print_r($term, TRUE),
            '@wpid2' => print_r($term_val, TRUE),
          ));
        $term_name = $term_val->getName();
        $term_list[] = $term_name;  
    }
    return $term_list;
  }
}
