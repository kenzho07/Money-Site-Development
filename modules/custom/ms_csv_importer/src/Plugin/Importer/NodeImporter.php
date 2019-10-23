<?php

namespace Drupal\ms_csv_importer\Plugin\Importer;

use Drupal\ms_csv_importer\Plugin\ImporterBase;

/**
 * Class NodeImporter.
 *
 * @Importer(
 *   id = "node_importer",
 *   entity_type = "node",
 *   label = @Translation("Node importer")
 * )
 */
class NodeImporter extends ImporterBase {}
