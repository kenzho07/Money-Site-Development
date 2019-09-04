<?php
/**
 * @file
 * @author Rakesh James
 * Contains \Drupal\private_message_adjust\Controller\ExampleController.
 * Please place this file under your private_message_adjust(module_root_folder)/src/Controller/
 */
namespace Drupal\private_message_adjust\Controller;
/**
 * Provides route responses for the Example module.
 */
use Symfony\Component\HttpFoundation\JsonResponse;

class PrivateMessageAdjustController {
  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */

  

  public function privateMessageAdjust() {

    return new JsonResponse(
      [
        'data' => $this->getResults(),
        'method' => 'GET'
      ]
    );
  }

  private function getResults(){
    $items = [
      ['name' => 'Article 1'],
      ['name' => 'Article 2'],
      ['name' => 'Article 3'],
      ['name' => 'Article 4'],
      ['name' => 'Article 5'],
    ];
    return $items;
  }
}
?>