<?php

namespace Drupal\ms_link_stats\Controller;

use Drupal\Core\Controller\ControllerBase;
Use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
Use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\node\Entity\Node;
Use Drupal\node\NodeInterface;
Use DOMDocument;

/**
 * Class LinkStatsController.
 */
class LinkStatsController extends ControllerBase {
  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new  object.
   * @param RequestStack $request_stack
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }
  /**
   * stats.
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function stats() {
    global $base_url;
    $all_links = [];
    //Get current request
    $requests = $this->requestStack->getCurrentRequest();
    if ($node = $requests->get('node')) {
      //Loading current Node
      if (gettype($node) == 'string' && !($node instanceof NodeInterface)) {
        $node = Node::load($node);
      }
      if ($node instanceof NodeInterface) {
        $check = $node->access('update', \Drupal::currentUser());
        // Print_r($check)
        if ($node->isDefaultRevision() == TRUE) {
          // Get all of the revision ids.
          $revision_ids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($node);
          $last_revision_id = end($revision_ids);
          // If its last/current revision
          if ($node->getRevisionId() == $last_revision_id) {
            // Load the revision.
            $last_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($last_revision_id);
            if ($last_revision instanceof NodeInterface && $last_revision->hasField('body')) {
              $bodyy = $last_revision->get('body')->value;
              // Create a new DOM document
              $dom = new DOMDocument;
              @$dom->loadHTML($bodyy);
              $links = $dom->getElementsByTagName('a');
              $totalUrls = count($links);
              // Iterate over the extracted links and display their URLs
              $brokenUrls = 0;
              foreach ($links as $link) {
                if ($Url = $link->getAttribute('href')) {
                  //create a list of all links
                  $linkHtml = "<tr><td><a href='" . $Url . "'>" . $link->nodeValue . "</a>";
                  //Check url is broken or not
                  if (!$this->checkUrlStatus($Url)) {
                    $brokenUrls++;
                    $linkHtml .= "<sup style='color:red'>broken</sup>";
                  }
                  $linkHtml .= "</td>";
                  if ($check) {
                    $linkHtml .= "<td><a href='" . $base_url . "/node/" . $node->id() . "/edit?href=" . $link->getAttribute('href') . "'>Edit</a></td>";
                  }
                  $linkHtml .= "</tr>";
                  $all_links[] = $linkHtml;
                }
                //$link->nodeValue;link name
                //$link->getAttribute('href');link
              }
              $header = [$this->t("Title"), $this->t("Value")];
              $rows[] = [$this->t("Total Links"), $totalUrls];
              $rows[] = [$this->t("Broken Links"), $brokenUrls];
              //Output a table with results
              $build['table_pager'][] = array (
                '#type' => 'table',
                '#header' => $header,
                '#rows' => $rows,
              );
              //output all links list
              if ($all_links) {
                $build['linkst_list'] = [
                  '#type' => 'markup',
                  '#markup' => $this->t("
                    <h2> List of links used in Content </h2>
                    <table><tr><th>Link</th>" . ($check ? "<th>Action</th>" : "") . "</tr>" . implode("", $all_links) . "</table>
                    "),
                ];
              }
              return $build;
            }
          }
        }
      }
    }
    //Output a No data result
    return ['#markup' => $this->t('There is no data!')];
  }

  /**
   * Check url if  broken
   */
  public function checkUrlStatus($url = null) {
    if ($url) {
      //add base url if there is no http
      //for internal urls
      if (strpos($url, 'http') === false && substr($url, 0, 4) != 'http') {
        global $base_url;
        $url = (substr($url, 0, 1) == '/' ? $base_url . $url : $base_url . '/' . $url);
      }
      //Using Curl to check link is broken or Not
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_VERBOSE, true);

      $result = curl_exec($curl);

      if ($result === false) {
        return false;
      }
      else {
        return true;
      }
      //close curl.
      curl_close($curl);
    }
    return false;
  }
}
