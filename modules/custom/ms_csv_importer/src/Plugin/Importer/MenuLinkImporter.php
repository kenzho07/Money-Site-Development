<?php

namespace Drupal\ms_csv_importer\Plugin\Importer;

use Drupal\ms_csv_importer\Plugin\ImporterBase;

/**
 * Class MenuLinkImporter.
 *
 * @Importer(
 *   id = "menu_link_content_importer",
 *   entity_type = "menu_link_content",
 *   label = @Translation("Menu link importer")
 * )
 */
class MenuLinkImporter extends ImporterBase {}
